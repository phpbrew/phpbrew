<?php
namespace PhpBrew\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\Builder;
use PhpBrew\VariantParser;
use PhpBrew\VariantBuilder;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\Tasks\MakeCleanTask;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\Tasks\InstallTask;
use PhpBrew\Tasks\ExtractTask;
use PhpBrew\Tasks\ConfigureTask;
use PhpBrew\Tasks\BuildTask;
use PhpBrew\Tasks\DSymTask;
use PhpBrew\Tasks\TestTask;
use PhpBrew\Build;
use PhpBrew\Utils;
use PhpBrew\ReleaseList;
use CLIFramework\Command;

/*
 * TODO: refactor tasks to Task class.
 */

class InstallCommand extends Command
{
    public function brief()
    {
        return 'Install php';
    }

    public function aliases() {
        return array('i','ins');
    }

    public function usage()
    {
        return 'phpbrew install [php-version] ([+variant...])';
    }

    public function arguments($args) {
        $args->add('version')->suggestions(array( '5.3', '5.4', '5.5', '5.6' ) );
        $args->add('variants')->multiple()->suggestions(function() {
            $variants = new VariantBuilder;
            $list = $variants->getVariantNames();
            sort($list);
            return array_map(function($n) { return '+' . $n; }, $list);
        });
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('test', 'Run tests after the installation.');

        $opts->add('alias:', 'The alias of the installation')->valueName('alias');

        $opts->add('clean', 'Run make clean before building.');

        $opts->add('mirror:', 'Use mirror specific site.');

        $opts->add('post-clean', 'Run make clean after building PHP.');

        $opts->add('production', 'Use production configuration');

        $opts->add('build-dir:','Specify the build directory')
            ->isa('dir')
            ;

        $opts->add('n|nice:', 'Runs build processes at an altered scheduling priority.')
            ->valueName('priority')
            ;

        $opts->add('patch+:', 'Apply patch before build.')
            ->isa('file')
            ;

        $opts->add('old', 'Install phpbrew incompatible phps (< 5.3)');

        $opts->add('f|force', 'Force the installation.');

        $opts->add('d|dryrun', 'Do not build, but run through all the tasks.');

        $opts->add('like:', 'Inherit variants from an existing build')
            ->valueName('version');

        $opts->add('j|make-jobs:', 'Specifies the number of jobs to run simultaneously (make -jN).')
            ->valueName('concurrent job number')
            ;
    }

    public function execute($version)
    {
        $version = preg_replace('/^php-/', '', $version);
        $releaseList = ReleaseList::getReadyInstance();
        $releases = $releaseList->getReleases();
        $versionInfo = $releaseList->getVersion($version);
        if (!$versionInfo) {
            throw new Exception("Version $version not found.");
        }
        $version = $versionInfo['version'];

        $distUrl = 'http://www.php.net/get/' . $versionInfo['filename'] . '/from/this/mirror';
        if ($mirrorSite = $this->options->mirror) {
            // http://tw1.php.net/distributions/php-5.3.29.tar.bz2
            $distUrl = $mirrorSite . '/distributions/' . $versionInfo['filename'];
        }

        // get options and variants for building php
        // and skip the first argument since it's the target version.
        $args = func_get_args();
        array_shift($args);

        $alias = $this->options->alias ?: $version;

        // Initialize the build object, contains the information to build php.
        $build = new Build($version, $alias);


        // find inherited variants
        if ($buildName = $this->options->like) {
            if ($parentBuild = Build::findByName(Utils::canonicalizeVersionName($buildName))) {
                $build->loadVariantInfo($parentBuild->settings->toArray());
            }
        }

        // ['extra_options'] => the extra options to be passed to ./configure command
        // ['enabled_variants'] => enabeld variants
        // ['disabled_variants'] => disabled variants
        $variantInfo = VariantParser::parseCommandArguments($args);
        $build->loadVariantInfo($variantInfo); // load again

        // assume +default variant if no build config is given and warn about that
        if (!$variantInfo['enabled_variants']) {
            $build->enableVariants(array(
                'bcmath' => true,
                'bz2' => true,
                'calendar' => true,
                'cli' => true,
                'ctype' => true,
                'dom' => true,
                'fileinfo' => true,
                'filter' => true,
                'ipc' => true,
                'json' => true,
                'mbregex' => true,
                'mbstring' => true,
                'mhash' => true,
                'pcntl' => true,
                'pcre' => true,
                'pdo' => true,
                'phar' => true,
                'posix' => true,
                'readline' => true,
                'sockets' => true,
                'tokenizer' => true,
                'xml' => true,
                'curl' => true,
                'zip' => true,
                'openssl' => 'yes',
            ));
            $this->logger->notice("You haven't used any '+' build variant. A default set of extensions will be installed:");
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

        $prepare = new PrepareDirectoryTask($this->logger, $this->options);
        $prepare->prepareForVersion($version);

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

        // Move to to build directory, because we are going to download distribution.
        $buildDir = $this->options->{'build-dir'} ?: Config::getBuildDir();
        if (!file_exists($buildDir)) {
            mkdir($buildDir, 0755, true);
        }

        $distFileDir = Config::getDistFileDir();
        $download = new DownloadTask($this->logger, $this->options);
        $targetFilePath = $download->download($distUrl, $versionInfo['md5'], $distFileDir);
        if (!file_exists($targetFilePath)) {
            throw new Exception("Download failed, $targetFilePath does not exist.");
        }

        $extract = new ExtractTask($this->logger, $this->options);
        $targetDir = $extract->extract($targetFilePath, $buildDir);
        if (!file_exists($targetDir)) {
            throw new Exception("Extract failed, $targetDir does not exist.");
        }

        // Change directory to the downloaded source directory.
        chdir($targetDir);

        $installPrefix = Config::getVersionInstallPrefix($version);
        if (!file_exists($installPrefix)) {
            mkdir($installPrefix, 0755, true);
        }

        $build->setInstallPrefix($installPrefix);
        $build->setSourceDirectory($targetDir);

        $this->logger->debug('Build Directory: ' . realpath($targetDir));

        $this->logger->debug('Loading and resolving variants...');
        $removedVariants = $build->loadVariantInfo($variantInfo);
        $this->logger->debug('Removed variants: ' . join(',', $removedVariants));

        // Write variants info.
        $variantInfoFile = $build->getInstallPrefix() . DIRECTORY_SEPARATOR . 'phpbrew.variants';
        $this->logger->debug("Writing variant info to $variantInfoFile");
        if ( false === $build->writeVariantInfoFile($variantInfoFile)) {
            $this->logger->warn("Can't store variant info.");
        }


        if ($this->options->clean) {
            $clean = new MakeCleanTask($this->logger, $this->options);
            $clean->clean($build);
        }

        $buildLogFile = $build->getBuildLogPath();

        $configure = new ConfigureTask($this->logger, $this->options);
        $configure->configure($build, $this->options);

        $buildTask = new BuildTask($this->logger, $this->options);
        $buildTask->setLogPath($buildLogFile);
        $buildTask->build($build, $this->options);

        if ($this->options->{'test'}) {
            $test = new TestTask($this->logger, $this->options);
            $test->setLogPath($buildLogFile);
            $test->test($build, $this->options);
        }

        $install = new InstallTask($this->logger, $this->options);
        $install->setLogPath($buildLogFile);
        $install->install($build, $this->options);

        if ($this->options->{'post-clean'}) {
            $clean = new MakeCleanTask($this->logger, $this->options);
            $clean->clean($build);
        }

        /** POST INSTALLATION **/
        $dsym = new DSymTask($this->logger, $this->options);
        $dsym->patch($build, $this->options);

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
        $phpConfigPath = $this->options->production ? 'php.ini-production' : 'php.ini-development';
        $this->logger->info("---> Copying $phpConfigPath ");

        if (file_exists($phpConfigPath)) {
            $targetConfigPath = $build->getEtcDirectory() . DIRECTORY_SEPARATOR . 'php.ini';

            if (file_exists($targetConfigPath)) {
                $this->logger->notice("Found existing $targetConfigPath.");
            } else {

                // TODO: Move this to PhpConfigPatchTask
                // move config file to target location
                copy($phpConfigPath, $targetConfigPath);

                // replace current timezone
                $timezone = ini_get('date.timezone');
                $pharReadonly = ini_get('phar.readonly');
                if ($timezone || $pharReadonly) {
                    // patch default config
                    $content = file_get_contents($targetConfigPath);
                    if ($timezone) {
                        $this->logger->info("---> Found date.timezone, patch config timezone with $timezone");
                        $content = preg_replace('/^date.timezone\s*=\s*.*/im', "date.timezone = $timezone", $content);
                    }
                    if (! $pharReadonly) {
                        $this->logger->info("---> Disable phar.readonly option.");
                        $content = preg_replace('/^phar.readonly\s*=\s*.*/im', "phar.readonly = 0", $content);
                    }
                    file_put_contents($targetConfigPath, $content);
                }
            }
        }

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

        $this->logger->debug("Source directory: " . $targetDir);

        $this->logger->info("Congratulations! Now you have PHP with $version.");

        echo <<<EOT
To use the newly built PHP, try the line(s) below:

    $ phpbrew use $version

Or you can use switch command to switch your default php version to $version:

    $ phpbrew switch $version

Enjoy!

EOT;

    }
}
