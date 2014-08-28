<?php
namespace PhpBrew\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\PhpSource;
use PhpBrew\Builder;
use PhpBrew\VariantParser;
use PhpBrew\VariantBuilder;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\Tasks\InstallTask;
use PhpBrew\Tasks\BuildTask;
use PhpBrew\Tasks\DSymTask;
use PhpBrew\Tasks\TestTask;
use PhpBrew\Build;
use CLIFramework\Command;

/*
 * TODO: refactor tasks to Task class.
 */

class InstallCommand extends Command
{
    public function brief()
    {
        return 'install php';
    }

    public function usage()
    {
        return 'phpbrew install [php-version] ([+variant...])';
    }

    public function arguments($args) {
        $args->add('version')->suggestions(array( '5.3', '5.4', '5.5' ) );
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
        $opts->add('test', 'run tests');
        $opts->add('name:', 'prefix name');
        $opts->add('clean', 'Run make clean before building.');
        $opts->add('post-clean', 'Run make clean after building PHP.');
        $opts->add('production', 'Use production configuration');
        $opts->add('n|nice:', 'process nice level');
        $opts->add('patch+:', 'apply patch before build')
            ->isa('file')
            ;
        $opts->add('old', 'install old phps (less than 5.3)');
        $opts->add('f|force', 'force');
        $opts->add('d|dryrun', 'dryrun');
        $opts->add('like:', 'inherit variants from previous build');
        $opts->add('j|make-jobs:', 'Specifies the number of jobs to run simultaneously (make -jN).');
    }

    public function execute($version)
    {
        if (!preg_match('/^php-/', $version)) {
            $version = 'php-' . $version;
        }

        $latestMinorVersion = $this->getLatestMinorVersion($version, $this->options->old);
        if (!empty($latestMinorVersion)) {
            $version = $latestMinorVersion;
        }

        $options = $this->options;
        $logger = $this->logger;

        // get options and variants for building php
        $args = func_get_args();
        // the first argument is the target version.
        array_shift($args);

        $name = $this->options->name ?: $version;

        // find inherited variants
        $inheritedVariants = array();
        if ($this->options->like) {
            $inheritedVariants = VariantParser::getInheritedVariants($this->options->like);
        }

        // ['extra_options'] => the extra options to be passed to ./configure command
        // ['enabled_variants'] => enabeld variants
        // ['disabled_variants'] => disabled variants
        $variantInfo = VariantParser::parseCommandArguments($args, $inheritedVariants);

        $info = PhpSource::getVersionInfo($version, $this->options->old);

        if (!$info) {
            throw new Exception("Version $version not found.");
        }

        $prepare = new PrepareDirectoryTask($this->logger);
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
        $buildDir = Config::getBuildDir();
        chdir($buildDir);

        $download = new DownloadTask($this->logger);
        $targetDir = $download->downloadByVersionString($version, $this->options->old, $this->options->force);

        if (!file_exists($targetDir)) {
            throw new Exception("Download failed.");
        }

        // Change directory to the downloaded source directory.
        chdir($targetDir);

        $buildPrefix = Config::getVersionBuildPrefix($version);

        if (!file_exists($buildPrefix)) {
            mkdir($buildPrefix, 0755, true);
        }

        // write variants info.
        $variantInfoFile = $buildPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants';
        $this->logger->debug("Writing variant info to $variantInfoFile");
        file_put_contents($variantInfoFile, serialize($variantInfo));

        // The build object, contains the information to build php.
        $build = new Build($version, $name, $buildPrefix);
        $build->setInstallPrefix($buildPrefix);
        $build->setSourceDirectory($targetDir);

        $builder = new Builder($targetDir, $version);
        $builder->logger = $this->logger;
        $builder->options = $this->options;

        $this->logger->debug('Build Directory: ' . realpath($targetDir));

        foreach ($variantInfo['enabled_variants'] as $name => $value) {
            $build->enableVariant($name, $value);
        }

        foreach ($variantInfo['disabled_variants'] as $name => $value) {
            $build->disableVariant($name);

            if ($build->hasVariant($name)) {
                $this->logger->warn("Removing variant $name since we've disabled it from command.");
                $build->removeVariant($name);
            }
        }

        $build->setExtraOptions($variantInfo['extra_options']);

        if ($options->clean) {
            $clean = new CleanTask($this->logger);
            $clean->cleanByVersion($version);
        }

        $buildLogFile = Config::getVersionBuildLogPath($version);

        $configure = new \PhpBrew\Tasks\ConfigureTask($this->logger);
        $configure->configure($build, $this->options);

        $buildTask = new BuildTask($this->logger);
        $buildTask->setLogPath($buildLogFile);
        $buildTask->build($build, $this->options);

        if ($options->{'test'}) {
            $test = new TestTask($this->logger);
            $test->setLogPath($buildLogFile);
            $test->test($build, $this->options);
        }

        $install = new InstallTask($this->logger);
        $install->setLogPath($buildLogFile);
        $install->install($build, $this->options);

        if ($options->{'post-clean'}) {
            $clean = new CleanTask($this->logger);
            $clean->cleanByVersion($version);
        }

        /** POST INSTALLATION **/
        $dsym = new DSymTask($this->logger);
        $dsym->patch($build, $this->options);

        // copy php-fpm config
        $this->logger->info("---> Creating php-fpm.conf");
        $phpFpmConfigPath = "sapi/fpm/php-fpm.conf";
        $phpFpmTargetConfigPath = Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php-fpm.conf';

        if (file_exists($phpFpmConfigPath)) {
            if (!file_exists($phpFpmTargetConfigPath)) {
                copy($phpFpmConfigPath, $phpFpmTargetConfigPath);
            } else {
                $this->logger->notice("Found existing $phpFpmTargetConfigPath.");
            }
        }

        $phpConfigPath = $options->production ? 'php.ini-production' : 'php.ini-development';
        $this->logger->info("---> Copying $phpConfigPath ");

        if (file_exists($phpConfigPath)) {
            $targetConfigPath = Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php.ini';

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

    private function getLatestMinorVersion($majorVersion, $includeOld)
    {
        $latestMinorVersion = '';
        foreach (array_keys(PhpSource::getStableVersions($includeOld)) as $version) {
            if (strpos($version, $majorVersion) === 0) {
                $latestMinorVersion = $version;
                break;
            }
        }

        return $latestMinorVersion;
    }
}
