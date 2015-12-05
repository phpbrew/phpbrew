<?php
namespace PhpBrew\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\VariantParser;
use PhpBrew\VariantBuilder;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\Tasks\MakeTask;
use PhpBrew\Tasks\InstallTask;
use PhpBrew\Tasks\ExtractTask;
use PhpBrew\Tasks\ConfigureTask;
use PhpBrew\Tasks\BuildTask;
use PhpBrew\Tasks\DSymTask;
use PhpBrew\Tasks\TestTask;
use PhpBrew\Build;
use PhpBrew\ReleaseList;
use PhpBrew\VersionDslParser;
use PhpBrew\BuildSettings\DefaultBuildSettings;
use PhpBrew\Distribution\DistributionUrlPolicy;
use PhpBrew\Exception\SystemCommandException;
use CLIFramework\ValueCollection;
use CLIFramework\Command;

class InstallCommand extends Command
{
    public function brief()
    {
        return 'Install php';
    }

    public function aliases()
    {
        return array('i','ins');
    }

    public function usage()
    {
        return 'phpbrew install [php-version] ([+variant...])';
    }

    public function arguments($args) {
        $args->add('version')->suggestions(function() {
            $releaseList = ReleaseList::getReadyInstance();
            $releases = $releaseList->getReleases();

            $collection = new ValueCollection;
            foreach($releases as $major => $versions) {
                $collection->group($major, "PHP $major", array_keys($versions));
            }

            $collection->group('pseudo', 'pseudo', array('latest', 'next'));

            return $collection;
        });
        $args->add('variants')->multiple()->suggestions(function() {
            $variants = new VariantBuilder;
            $list = $variants->getVariantNames();
            sort($list);
            return array_map(function($n) { return '+' . $n; }, $list);
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
     * @param \GetOptionKit\OptionCollection $opts
     */
    public function options($opts)
    {
        $opts->add('test', 'Run tests after the installation.');

        $opts->add('name:', 'The name of the installation. By default the installed path is equal to the release version name (php-5.x.x), however you can specify a custom name instead of the default `php-5.x.x`. For example, `myphp-5.3.2-dbg`')
            ->valueName('name');

        $opts->add('mirror:', 'Use specified mirror site. phpbrew will download the files from [mirror]/distributions/*');

        $opts->add('post-clean', 'Run make clean after the installation.');

        $opts->add('production', 'Use production configuration file. this installer will copy the php-production.ini into the etc directory.');

        $opts->add('build-dir:','Specify the build directory. '
            . 'the distribution tarball will be extracted to the directory you specified '
            . 'instead of $PHPBREW_ROOT/build/{name}.')
            ->isa('dir')
            ;

        $opts->add('root','specify phpbrew root instead of PHPBREW_ROOT');

        $opts->add('home','specify phpbrew home instead of PHPBREW_HOME');

        $opts->add('no-clean', 'Do not clean previously compiled objects before building PHP. ' 
            . 'By default phpbrew will run `make clean` before running the configure script '
            . 'to ensure everything is cleaned up.')
            ;

        $opts->add('no-patch', 'Do not apply any patch');

        $opts->add('no-configure', 'Do not run configure script');

        $opts->add('no-install', 'Do not install, just run build the target');

        $opts->add('n|nice:', 'Runs build processes at an altered scheduling priority. The priority can be adjusted over a range of -20 (the highest) to 20 (the lowest).')
            ->valueName('priority');

        $opts->add('patch+:', 'Apply patch before build.')
            ->isa('file');

        $opts->add('old', 'Install phpbrew incompatible phps (< 5.3)');

        $opts->add('http-proxy:', 'The HTTP Proxy to download PHP distributions. e.g. --http-proxy=22.33.44.55:8080')
            ->valueName('proxy host')
            ;

        $opts->add('http-proxy-auth:', 'The HTTP Proxy Auth to download PHP distributions. user:pass')
            ->valueName('user:pass')
            ;

        $opts->add('user-config','Allow users create their own config file (php.ini or extension config init files)');

        $opts->add('connect-timeout:', 'The system aborts the command if downloading '
                . 'of a php version not starts during this limit. This option '
                . 'overrides a value of CONNECT_TIMEOUT environment variable.')
            ->valueName('seconds')
            ;
        
        $opts->add('f|force', 'Force the installation (redownloads source).')
            ->defaultValue(false)
            ;

        $opts->add('d|dryrun', 'Do not build, but run through all the tasks.');

        $opts->add('like:', 'Inherit variants from an existing build. This option would require an existing build directory from the {version}.')
            ->valueName('version');

        $opts->add('j|jobs:', 'Specifies the number of jobs to run build simultaneously (make -jN).')
            ->valueName('concurrent job number')
            ;
        $opts->add('stdout', 'Outputs install logs to stdout.');
    }

    public function execute($version)
    {
        $distUrl = NULL;
        $versionInfo = array();
        $releaseList = ReleaseList::getReadyInstance();
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
            $version = "github.com/php/php-src:master";
        }

        if ($info = $versionDslParser->parse($version)) {
            $version = $info['version'];
            $distUrl = $info['url'];
            // always redownload when installing from github master
            // beware to keep this behavior after clean up the TODO below
            $this->options['force']->setValue(true);
        } else {
            // TODO ↓ clean later ↓ d.d.d versions should be part of the DSL too
            $version = preg_replace('/^php-/', '', $version);
            $versionInfo = $releaseList->getVersion($version);
            if (!$versionInfo) {
                throw new Exception("Version $version not found.");
            }
            $version = $versionInfo['version'];

            $distUrlPolicy = new DistributionUrlPolicy();
            if ($this->options->mirror) {
                $distUrlPolicy->setMirrorSite($this->options->mirror);
            }
            $distUrl = $distUrlPolicy->buildUrl($version, $versionInfo['filename']);
        }

        // get options and variants for building php
        // and skip the first argument since it's the target version.
        $args = func_get_args();
        array_shift($args); // shift the version name

        $semanticOptions = $this->parseSemanticOptions($args);
        $buildAs =   isset($semanticOptions['as']) ? $semanticOptions['as'] : $this->options->name;
        $buildLike = isset($semanticOptions['like']) ? $semanticOptions['like'] : $this->options->like;

        // convert patch to realpath
        if ($this->options->patch) {
            $patchPaths = array();
            foreach ($this->options->patch as $patch) {
                /** @var \SplFileInfo $patch */
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
                $build->loadVariantInfo($parentBuild->settings->toArray());
            }
        }


        $msg = "===> phpbrew will now build {$build->getVersion()}";
        if ($buildLike) {
            $msg .= ' using variants from ' . $buildLike;
        }
        if (isset($semanticOptions['using'])) {
            $msg .= ' plus custom variants: ' . join(', ',$semanticOptions['using']);
            $args = array_merge($args, $semanticOptions['using']);
        }
        if ($buildAs) {
            $msg .= ' as ' . $buildAs;
        }
        $this->logger->info($msg);

        if (!empty($args)) {
            $this->logger->debug("---> Parsing variants from command arguments '" . join(' ', $args) . "'");
        }

        // ['extra_options'] => the extra options to be passed to ./configure command
        // ['enabled_variants'] => enabeld variants
        // ['disabled_variants'] => disabled variants
        $variantInfo = VariantParser::parseCommandArguments($args);
        $build->loadVariantInfo($variantInfo); // load again

        // assume +default variant if no build config is given and warn about that
        if (!$variantInfo['enabled_variants']) {
            $build->setBuildSettings(new DefaultBuildSettings());
            $this->logger->notice("You haven't set any variant. A default set of extensions will be installed for the minimum requirement:");
            $this->logger->notice('[' . implode(', ', array_keys($build->getVariants())) . ']');
            $this->logger->notice("Please run 'phpbrew variants' for more information.\n");
        }

        if (preg_match('/5\.3\./',$version)) {
            $this->logger->notice("PHP 5.3 requires +intl, enabled by default.");
            $build->enableVariant('intl');
        }

        // always add +xml by default unless --without-pear is present
        // TODO: This can be done by "-pear"
        if(! in_array('--without-pear', $variantInfo['extra_options'])){
            $build->enableVariant('xml');
        }

        $this->logger->info('===> Loading and resolving variants...');
        $removedVariants = $build->loadVariantInfo($variantInfo);
        if (!empty($removedVariants)) {
            $this->logger->debug('Removed variants: ' . join(',', $removedVariants));
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

        $variantBuilder = new VariantBuilder;
        $configureOptions = $variantBuilder->build($build);

        $distFileDir = Config::getDistFileDir();

        $downloadTask = new DownloadTask($this->logger, $this->options);
        $targetFilePath = $downloadTask->download($distUrl, $distFileDir, isset($versionInfo['md5']) ? $versionInfo['md5'] : NULL);
        if (!file_exists($targetFilePath)) {
            throw new Exception("Download failed, $targetFilePath does not exist.");
        }
        unset($downloadTask);

        $extractTask = new ExtractTask($this->logger, $this->options);
        $targetDir = $extractTask->extract($build, $targetFilePath, $buildDir);
        if (!file_exists($targetDir)) {
            throw new Exception("Extract failed, $targetDir does not exist.");
        }
        unset($extractTask);

        // Update build source directory 
        $this->logger->debug('Source Directory: ' . realpath($targetDir));
        $build->setSourceDirectory($targetDir);

        if (!$this->options->{'no-clean'} && file_exists($targetDir . DIRECTORY_SEPARATOR . 'Makefile') ) {
            $this->logger->info("Found existing Makefile, running make clean to ensure everything will be rebuilt.");
            $this->logger->info("You can append --no-clean option after the install command if you don't want to rebuild.");
            $clean->clean($build);
        }

        // Change directory to the downloaded source directory.
        chdir($targetDir);
        // Write variants info.
        $variantInfoFile = $build->getInstallPrefix() . DIRECTORY_SEPARATOR . 'phpbrew.variants';
        $this->logger->debug("Writing variant info to $variantInfoFile");
        if ( false === $build->writeVariantInfoFile($variantInfoFile)) {
            $this->logger->warn("Can't store variant info.");
        }

        $buildLogFile = $build->getBuildLogPath();

        try {

            if (!$this->options->{'no-configure'}) {
                $configureTask = new ConfigureTask($this->logger, $this->options);
                $configureTask->run($build, $configureOptions);
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

            /** POST INSTALLATION **/
            {
                $dsym = new DSymTask($this->logger, $this->options);
                $dsym->patch($build, $this->options);
            }
        } catch (SystemCommandException $e) {
            $buildLog = $e->getLogFile();
            $this->logger->error("Error: " . $e->getMessage());
            $this->logger->error("Configure options: " . join(' ', $configureOptions));
            $this->logger->error("Last 5 lines in the log file:");

            $lines = array_slice(file($buildLog), -5);
            foreach ($lines as $line) {
                echo $line , PHP_EOL;
            }

            $this->logger->error("Please checkout the build log file for more details:");
            $this->logger->error("\t tail $buildLog");
            return;
        }

        // copy php-fpm config
        $this->logger->info("---> Creating php-fpm.conf");
        $phpFpmConfigPath = "sapi/fpm/php-fpm.conf";
        $phpFpmTargetConfigPath = $build->getEtcDirectory() . DIRECTORY_SEPARATOR . 'php-fpm.conf';
        if (file_exists($phpFpmConfigPath)) {
            if (!file_exists($phpFpmTargetConfigPath)) {
                copy($phpFpmConfigPath, $phpFpmTargetConfigPath);
            } else {
                $this->logger->notice("Found existing $phpFpmTargetConfigPath.");
            }
        }



        $this->logger->info("---> Creating php.ini");
        $phpConfigPath = $build->getSourceDirectory()
             . DIRECTORY_SEPARATOR . ($this->options->production ? 'php.ini-production' : 'php.ini-development');
        $this->logger->info("---> Copying $phpConfigPath ");

        if (file_exists($phpConfigPath) && ! $this->options->dryrun) {
            $targetConfigPath = $build->getEtcDirectory() . DIRECTORY_SEPARATOR . 'php.ini';

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
                $patched = false;

                if (!isset($config['date']['timezone'])) {
                    $this->logger->info('---> Found date.timezone is not set, patching...');

                    // Replace current timezone
                    if ($timezone = ini_get('date.timezone')) {
                        $this->logger->info("---> Found date.timezone, patching config timezone with $timezone");
                        $configContent = preg_replace('/^;?date.timezone\s*=\s*.*/im', "date.timezone = $timezone", $configContent);
                    }
                    $patched = true;
                }
                if (!isset($config['phar']['readonly'])) {
                    $pharReadonly = ini_get('phar.readonly');
                    // 0 or "" means readonly is disabled manually
                    if (!$pharReadonly) {
                        $this->logger->info("---> Disabling phar.readonly option.");
                        $configContent = preg_replace('/^;?phar.readonly\s*=\s*.*/im', "phar.readonly = 0", $configContent);
                    }
                }
                file_put_contents($targetConfigPath, $configContent);
            }
        }


        if ($build->isEnabledVariant('pear')) {
            $this->logger->info("Initializing pear config...");
            $home = Config::getPhpbrewHome();

            @mkdir("$home/tmp/pear/temp", 0755, true);
            @mkdir("$home/tmp/pear/cache_dir", 0755, true);
            @mkdir("$home/tmp/pear/download_dir", 0755, true);

            system("pear config-set temp_dir $home/tmp/pear/temp");
            system("pear config-set cache_dir $home/tmp/pear/cache_dir");
            system("pear config-set download_dir $home/tmp/pear/download_dir");

            $this->logger->info("Enabling pear auto-discover...");
            system("pear config-set auto_discover 1");
        }

        $this->logger->debug("Source directory: " . $targetDir);

        $buildName = $build->getName();

        $this->logger->info("Congratulations! Now you have PHP with $version as $buildName");

        echo <<<EOT
To use the newly built PHP, try the line(s) below:

    $ phpbrew use $buildName

Or you can use switch command to switch your default php to $buildName:

    $ phpbrew switch $buildName

Enjoy!

EOT;

    }
}
