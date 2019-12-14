<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use CLIFramework\ValueCollection;
use Exception;
use GetOptionKit\OptionCollection;
use PhpBrew\Build;
use PhpBrew\Config;
use PhpBrew\Distribution\DistributionUrlPolicy;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Exception\SystemCommandException;
use PhpBrew\ReleaseList;
use PhpBrew\Tasks\AfterConfigureTask;
use PhpBrew\Tasks\BeforeConfigureTask;
use PhpBrew\Tasks\BuildTask;
use PhpBrew\Tasks\ConfigureTask;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\DSymTask;
use PhpBrew\Tasks\ExtractTask;
use PhpBrew\Tasks\InstallTask;
use PhpBrew\Tasks\MakeTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\Tasks\TestTask;
use PhpBrew\VariantBuilder;
use PhpBrew\VariantParser;
use PhpBrew\VersionDslParser;
use SplFileInfo;

class InstallCommand extends Command
{
    public function brief()
    {
        return 'Install php';
    }

    public function aliases()
    {
        return array('i', 'ins');
    }

    public function usage()
    {
        return 'phpbrew install [php-version] ([+variant...])';
    }

    public function arguments($args)
    {
        $args->add('version')->suggestions(function () {
            $releaseList = ReleaseList::getReadyInstance();
            $releases = $releaseList->getReleases();

            $collection = new ValueCollection();
            foreach ($releases as $major => $versions) {
                $collection->group($major, "PHP $major", array_keys($versions));
            }

            $collection->group('pseudo', 'pseudo', array('latest', 'next'));

            return $collection;
        });
        $args->add('variants')->multiple()->suggestions(function () {
            $variants = new VariantBuilder();
            $list = $variants->getVariantNames();
            sort($list);

            return array_map(function ($n) {
                return '+' . $n;
            }, $list);
        });
    }

    public function parseSemanticOptions(array &$args)
    {
        $settings = array();

        $definitions = array(
            'as' => '*',
            'like' => '*',
            'using' => '*+',
        );

        // XXX: support 'using'
        foreach ($definitions as $k => $requirement) {
            $idx = array_search($k, $args);

            if ($idx !== false) {
                if ($requirement == '*') {
                    // Find the value next to the position
                    list($key, $val) = array_splice($args, $idx, 2);
                    $settings[$key] = $val;
                } elseif ($requirement == '*+') {
                    $values = array_splice($args, $idx, 2);
                    $key = array_shift($values);
                    $settings[$key] = $values;
                }
            }
        }

        return $settings;
    }

    /**
     * @param OptionCollection $opts
     */
    public function options($opts)
    {
        $opts->add('test', 'Run tests after the installation.');

        $opts->add(
            'name:',
            'The name of the installation. '
            . 'By default the installed path is equal to the release version name (php-5.x.x), '
            . 'however you can specify a custom name instead of the default `php-5.x.x`. For example, `myphp-5.3.2-dbg`'
        )
            ->valueName('name');

        $opts->add('post-clean', 'Run make clean after the installation.');

        $opts->add(
            'production',
            'Use production configuration file. this installer will copy the php-production.ini into the etc directory.'
        );

        $opts->add('build-dir:', 'Specify the build directory. '
            . 'the distribution tarball will be extracted to the directory you specified '
            . 'instead of $PHPBREW_ROOT/build/{name}.')
            ->isa('dir')
            ;

        $opts->add('root', 'Specify PHPBrew root instead of PHPBREW_ROOT');

        $opts->add('home', 'Specify PHPBrew home instead of PHPBREW_HOME');

        $opts->add('no-config-cache', 'Do not use config.cache for configure script.');

        $opts->add('no-clean', 'Do not clean previously compiled objects before building PHP. '
            . 'By default phpbrew will run `make clean` before running the configure script '
            . 'to ensure everything is cleaned up.')
            ;

        $opts->add('no-patch', 'Do not apply any patch');

        $opts->add('no-configure', 'Do not run configure script');

        $opts->add('no-install', 'Do not install, just run build the target');

        $opts->add(
            'n|nice:',
            'Runs build processes at an altered scheduling priority. '
            . 'The priority can be adjusted over a range of -20 (the highest) to 20 (the lowest).'
        )
            ->valueName('priority');

        $opts->add('patch+:', 'Apply patch before build.')
            ->isa('file');

        $opts->add('old', 'Install phpbrew incompatible phps (< 5.3)');

        $opts->add('user-config', 'Allow users create their own config file (php.ini or extension config init files)');

        DownloadFactory::addOptionsForCommand($opts);

        $opts->add('f|force', 'Force the installation (redownloads source).')
            ->defaultValue(false)
            ;

        $opts->add('d|dryrun', 'Do not build, but run through all the tasks.');

        $opts->add(
            'like:',
            'Inherit variants from an existing build. '
            . 'This option would require an existing build directory from the {version}.'
        )
            ->valueName('version');

        $opts->add('j|jobs:', 'Specifies the number of jobs to run build simultaneously (make -jN).')
            ->valueName('concurrent job number')
            ;
        $opts->add('stdout', 'Outputs install logs to stdout.');

        $opts->add('sudo', 'sudo to run install command.');
    }

    public function execute($version)
    {
        if (extension_loaded('posix') && posix_getuid() === 0) {
            $this->logger->warn(
                "*WARNING* You're running phpbrew as root/sudo. Unless you're going to install "
                . "system-wide phpbrew, this might cause problems."
            );
            sleep(3);
        }
        $distUrl = null;
        $versionInfo = array();
        $releaseList = ReleaseList::getReadyInstance($this->options);
        $versionDslParser = new VersionDslParser();
        $clean = new MakeTask($this->logger, $this->options);
        $clean->setQuiet();

        if ($root = $this->options->root) {
            Config::setPhpbrewRoot($root);
        }
        if ($home = $this->options->home) {
            Config::setPhpbrewHome($home);
        }

        if ('latest' === strtolower($version)) {
            $version = $releaseList->getLatestVersion();
        }

        // this should point to master or the latest version branch yet to be released
        if ('next' === strtolower($version)) {
            $version = 'github.com/php/php-src:master';
        }

        if ($info = $versionDslParser->parse($version)) {
            $version = $info['version'];
            $distUrl = $info['url'];

            // re-download when installing not from a tag
            // beware to keep this behavior after clean up the TODO below
            $this->options['force']->setValue(
                empty($info['is_tag'])
            );
        } else {
            // TODO ↓ clean later ↓ d.d.d versions should be part of the DSL too
            $version = preg_replace('/^php-/', '', $version);
            $versionInfo = $releaseList->getVersion($version);
            if (!$versionInfo) {
                throw new Exception("Version $version not found.");
            }
            $version = $versionInfo['version'];

            $distUrlPolicy = new DistributionUrlPolicy();
            $distUrl = $distUrlPolicy->buildUrl($version, $versionInfo['filename'], $versionInfo['museum']);
        }

        // get options and variants for building php
        // and skip the first argument since it's the target version.
        $args = func_get_args();
        array_shift($args); // shift the version name

        $semanticOptions = $this->parseSemanticOptions($args);
        $buildAs = isset($semanticOptions['as']) ? $semanticOptions['as'] : $this->options->name;
        $buildLike = isset($semanticOptions['like']) ? $semanticOptions['like'] : $this->options->like;

        // convert patch to realpath
        if ($this->options->patch) {
            $patchPaths = array();
            foreach ($this->options->patch as $patch) {
                /* @var SplFileInfo $patch */
                $patchPath = realpath($patch);
                if ($patchPath !== false) {
                    $patchPaths[(string) $patch] = $patchPath;
                }
            }
            // rewrite patch paths
            $this->options->keys['patch']->value = $patchPaths;
        }

        // Initialize the build object, contains the information to build php.
        $build = new Build($version, $buildAs);

        $installPrefix = Config::getInstallPrefix() . DIRECTORY_SEPARATOR . $build->getName();
        if (!file_exists($installPrefix)) {
            mkdir($installPrefix, 0755, true);
        }
        $build->setInstallPrefix($installPrefix);

        // find inherited variants
        if ($buildLike) {
            if ($parentBuild = Build::findByName($buildLike)) {
                $this->logger->info("===> Loading build settings from $buildLike");
                $build->loadVariantInfo($parentBuild->settings->toArray());
            }
        }

        $msg = "===> phpbrew will now build {$build->getVersion()}";
        if ($buildLike) {
            $msg .= ' using variants from ' . $buildLike;
        }
        if (isset($semanticOptions['using'])) {
            $msg .= ' plus custom variants: ' . implode(', ', $semanticOptions['using']);
            $args = array_merge($args, $semanticOptions['using']);
        }
        if ($buildAs) {
            $msg .= ' as ' . $buildAs;
        }
        $this->logger->info($msg);

        if (!empty($args)) {
            $this->logger->debug("---> Parsing variants from command arguments '" . implode(' ', $args) . "'");
        }

        // ['extra_options'] => the extra options to be passed to ./configure command
        // ['enabled_variants'] => enabeld variants
        // ['disabled_variants'] => disabled variants
        $variantInfo = VariantParser::parseCommandArguments($args, $this->logger);
        $build->loadVariantInfo($variantInfo); // load again

        // assume +default variant if no build config is given
        if (!$variantInfo['enabled_variants']) {
            $build->settings->enableVariant('default');
            $this->logger->notice(
                "You haven't enabled any variants. The default variant will be enabled: "
            );
            $builder = new VariantBuilder();
            $this->logger->notice('[' . implode(', ', $builder->virtualVariants['default']) . ']');
            $this->logger->notice("Please run 'phpbrew variants' for more information.\n");
        }

        if (preg_match('/5\.3\./', $version)) {
            $this->logger->notice('PHP 5.3 requires +intl, enabled by default.');
            $build->enableVariant('intl');
        }

        // always add +xml by default unless --without-pear is present
        // TODO: This can be done by "-pear"
        if (!in_array('--without-pear', $variantInfo['extra_options'])) {
            $build->enableVariant('xml');
        }

        $this->logger->info('===> Loading and resolving variants...');
        $removedVariants = $build->loadVariantInfo($variantInfo);
        if (!empty($removedVariants)) {
            $this->logger->debug('Removed variants: ' . implode(',', $removedVariants));
        }

        {
            $prepareTask = new PrepareDirectoryTask($this->logger, $this->options);
            $prepareTask->run($build);
        }

        // Move to to build directory, because we are going to download distribution.
        $buildDir = $this->options->{'build-dir'} ?: Config::getBuildDir();
        if (!file_exists($buildDir)) {
            mkdir($buildDir, 0755, true);
        }

        $variantBuilder = new VariantBuilder();
        $configureOptions = $variantBuilder->build($build);

        $distFileDir = Config::getDistFileDir();

        $downloadTask = new DownloadTask($this->logger, $this->options);
        $algo = 'md5';
        $hash = null;
        if (isset($versionInfo['sha256'])) {
            $algo = 'sha256';
            $hash = $versionInfo['sha256'];
        } elseif (isset($versionInfo['md5'])) {
            $algo = 'md5';
            $hash = $versionInfo['md5'];
        }
        $targetFilePath = $downloadTask->download($distUrl, $distFileDir, $algo, $hash);
        if (!file_exists($targetFilePath)) {
            throw new SystemCommandException("Download failed, $targetFilePath does not exist.", $build);
        }
        unset($downloadTask);

        $extractTask = new ExtractTask($this->logger, $this->options);
        $targetDir = $extractTask->extract($build, $targetFilePath, $buildDir);
        if (!file_exists($targetDir)) {
            throw new SystemCommandException("Extract failed, $targetDir does not exist.", $build);
        }
        unset($extractTask);

        // Update build source directory
        $this->logger->debug('Source Directory: ' . realpath($targetDir));
        $build->setSourceDirectory($targetDir);

        if (!$this->options->{'no-clean'} && file_exists($targetDir . DIRECTORY_SEPARATOR . 'Makefile')) {
            $this->logger->info('Found existing Makefile, running make clean to ensure everything will be rebuilt.');
            $this->logger->info(
                "You can append --no-clean option after the install command if you don't want to rebuild."
            );
            $clean->clean($build);
        }

        // Change directory to the downloaded source directory.
        chdir($targetDir);
        // Write variants info.
        $variantInfoFile = $build->getInstallPrefix() . DIRECTORY_SEPARATOR . 'phpbrew.variants';
        $this->logger->debug("Writing variant info to $variantInfoFile");
        if (false === $build->writeVariantInfoFile($variantInfoFile)) {
            $this->logger->warn("Can't store variant info.");
        }

        if (!$this->options->{'no-configure'}) {
            $configureTask = new BeforeConfigureTask($this->logger, $this->options);
            $configureTask->run($build);
            unset($configureTask); // trigger __destruct

            $configureTask = new ConfigureTask($this->logger, $this->options);
            $configureTask->run($build, $configureOptions);
            unset($configureTask); // trigger __destruct

            $configureTask = new AfterConfigureTask($this->logger, $this->options);
            $configureTask->run($build);
            unset($configureTask); // trigger __destruct
        }

        {
            $buildTask = new BuildTask($this->logger, $this->options);
            $buildTask->run($build);
            unset($buildTask); // trigger __destruct
        }

        if ($this->options->{'test'}) {
            $testTask = new TestTask($this->logger, $this->options);
            $testTask->run($build);
            unset($testTask); // trigger __destruct
        }

        if (!$this->options->{'no-install'}) {
            $installTask = new InstallTask($this->logger, $this->options);
            $installTask->install($build);
            unset($installTask); // trigger __destruct
        }

        if ($this->options->{'post-clean'}) {
            $clean->clean($build);
        }

        /* POST INSTALLATION **/
        {
            $dsym = new DSymTask($this->logger, $this->options);
            $dsym->patch($build, $this->options);
        }

        // copy php-fpm config
        $this->logger->info('---> Creating php-fpm.conf');
        $etcDirectory = $build->getEtcDirectory();
        $fpmUnixSocket = $build->getInstallPrefix() . "/var/run/php-fpm.sock";
        $this->installAs("$etcDirectory/php-fpm.conf.default", "$etcDirectory/php-fpm.conf");
        $this->installAs("$etcDirectory/php-fpm.d/www.conf.default", "$etcDirectory/php-fpm.d/www.conf");

        $patchingFiles = array("$etcDirectory/php-fpm.d/www.conf", "$etcDirectory/php-fpm.conf");
        foreach ($patchingFiles as $patchingFile) {
            if (file_exists($patchingFile)) {
                $this->logger->info("---> Found $patchingFile");
                // Patch pool listen unix
                // The original config was below:
                //
                // listen = 127.0.0.1:9000
                //
                // See http://php.net/manual/en/install.fpm.configuration.php for more details
                $ini = file_get_contents($patchingFile);
                $this->logger->info("---> Patching default fpm pool listen path to $fpmUnixSocket");
                $ini = preg_replace('/^listen = .*$/m', "listen = $fpmUnixSocket\n", $ini);
                file_put_contents($patchingFile, $ini);
                break;
            }
        }


        $this->logger->info('---> Creating php.ini');
        $phpConfigPath = $build->getSourceDirectory()
             . DIRECTORY_SEPARATOR . ($this->options->production ? 'php.ini-production' : 'php.ini-development');
        $this->logger->info("---> Copying $phpConfigPath ");

        if (file_exists($phpConfigPath) && !$this->options->dryrun) {
            $targetConfigPath = $etcDirectory . DIRECTORY_SEPARATOR . 'php.ini';

            if (file_exists($targetConfigPath)) {
                $this->logger->notice("Found existing $targetConfigPath.");
            } else {
                // TODO: Move this to PhpConfigPatchTask
                // move config file to target location
                copy($phpConfigPath, $targetConfigPath);
            }

            if (!$this->options->{'no-patch'}) {
                $config = parse_ini_file($targetConfigPath, true);
                $configContent = file_get_contents($targetConfigPath);

                if (!isset($config['date']['timezone'])) {
                    $this->logger->info('---> Found date.timezone is not set, patching...');

                    // Replace current timezone
                    if ($timezone = ini_get('date.timezone')) {
                        $this->logger->info("---> Found date.timezone, patching config timezone with $timezone");
                        $configContent = preg_replace(
                            '/^;?date.timezone\s*=\s*.*/im',
                            "date.timezone = $timezone",
                            $configContent
                        );
                    }
                }

                if (!isset($config['phar']['readonly'])) {
                    $pharReadonly = ini_get('phar.readonly');
                    // 0 or "" means readonly is disabled manually
                    if (!$pharReadonly) {
                        $this->logger->info('---> Disabling phar.readonly option.');
                        $configContent = preg_replace(
                            '/^;?phar.readonly\s*=\s*.*/im',
                            'phar.readonly = 0',
                            $configContent
                        );
                    }
                }

                // turn off detect_encoding for 5.3
                if ($build->compareVersion('5.4') < 0) {
                    $this->logger->info("---> Turn off detect_encoding for php 5.3.*");
                    $configContent = $configContent . "\ndetect_unicode = Off\n";
                }

                file_put_contents($targetConfigPath, $configContent);
            }
        }

        if ($build->isEnabledVariant('pear')) {
            $this->logger->info('Initializing pear config...');
            $home = Config::getHome();

            @mkdir("$home/tmp/pear/temp", 0755, true);
            @mkdir("$home/tmp/pear/cache_dir", 0755, true);
            @mkdir("$home/tmp/pear/download_dir", 0755, true);

            system("pear config-set temp_dir $home/tmp/pear/temp");
            system("pear config-set cache_dir $home/tmp/pear/cache_dir");
            system("pear config-set download_dir $home/tmp/pear/download_dir");

            $this->logger->info('Enabling pear auto-discover...');
            system('pear config-set auto_discover 1');
        }

        $this->logger->debug('Source directory: ' . $targetDir);

        $buildName = $build->getName();

        $this->logger->info("Congratulations! Now you have PHP with $version as $buildName");

        if ($build->isEnabledVariant('pdo') && $build->isEnabledVariant('mysql')) {
            echo <<<EOT

* We found that you enabled 'mysql' variant, you might need to setup your
  'pdo_mysql.default_socket' or 'mysqli.default_socket' in your php.ini file.

EOT;
        }

        if (isset($targetConfigPath)) {
            echo <<<EOT

* To configure your installed PHP further, you can edit the config file at
    $targetConfigPath

EOT;
        }

        // If the bashrc file is not found, it means 'init' command didn't get
        // a chance to be executed.
        if (!file_exists(Config::getHome() . DIRECTORY_SEPARATOR . 'bashrc')) {
            echo <<<EOT

* WARNING:
  You haven't run 'phpbrew init' yet! Be sure to setup your phpbrew to use your own php(s)
  Please run 'phpbrew init' to setup your phpbrew in place.

EOT;
        }

        // If the environment variable is not defined, it means users didn't
        // setup ther .bashrc or .zshrc
        if (!getenv('PHPBREW_HOME')) {
            echo <<<EOT

* WARNING:
  You haven't setup your .bashrc file to load phpbrew shell script yet!
  Please run 'phpbrew init' to see the steps!

EOT;
        }

        echo <<<EOT

To use the newly built PHP, try the line(s) below:

    $ phpbrew use $buildName

Or you can use switch command to switch your default php to $buildName:

    $ phpbrew switch $buildName

Enjoy!

EOT;
    }


    protected function installAs($source, $target, $override = false)
    {
        if (file_exists($source)) {
            if ($override || !file_exists($target)) {
                return copy($source, $target);
            } else {
                $this->logger->notice("Found existing $target.");
                return false;
            }
        }
    }
}
