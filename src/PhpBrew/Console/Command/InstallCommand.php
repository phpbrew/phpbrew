<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;
use PhpBrew\Builder;
use PhpBrew\VariantParser;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\Tasks\InstallTask;
use PhpBrew\Tasks\BuildTask;
use PhpBrew\Build;
use PhpBrew\DirectorySwitch;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * TODO: refactor tasks to Task class.
 */

class InstallCommand extends Command
{
    /* public function usage()
    {
        return 'phpbrew install [php-version] ([+variant...])';
    }

    public function options($opts)
    {
        $opts->add('test','run tests');
        $opts->add('name:','prefix name');
        $opts->add('clean','Run make clean before building.');
        $opts->add('post-clean','Run make clean after building PHP.');
        $opts->add('production','Use production configuration');
        $opts->add('n|nice:', 'process nice level');
        $opts->add('patch:',  'apply patch before build');
        $opts->add('old','install old phps (less than 5.3)');
        $opts->add('f|force','force');
    } */

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install php.')
            ->setDefinition(array(
                new InputArgument('version', InputArgument::OPTIONAL, 'The php version to download'),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');

        if( ! preg_match('/^php-/', $version) )
            $version = 'php-' . $version;

        $options = $this->options;
        $logger = $this->logger;

        // get options and variants for building php
        $args = func_get_args();
        // the first argument is the target version.
        array_shift($args);

        $name = $this->options->name ?: $version;

        // ['extra_options'] => the extra options to be passed to ./configure command
        // ['enabled_variants'] => enabeld variants
        // ['disabled_variants'] => disabled variants
        $variantInfo = VariantParser::parseCommandArguments($args);


        $info = PhpSource::getVersionInfo( $version, $this->options->old );
        if( ! $info)
            throw new Exception("Version $version not found.");

        $prepare = new PrepareDirectoryTask($this->logger);
        $prepare->prepareForVersion($version);




        // convert patch to realpath
        if( $this->options->patch ) {
            $patch = realpath($this->options->patch);
            // rewrite patch path
            $this->options->keys['patch']->value = $patch;
        }

        // Move to to build directory, because we are going to download distribution.
        $buildDir = Config::getBuildDir();
        chdir($buildDir);

        $download = new DownloadTask($this->logger);
        $targetDir = $download->downloadByVersionString($version, $this->options->old , $this->options->force );

        if( ! file_exists($targetDir) ) {
            throw new Exception("Download failed.");
        }

        // Change directory to the downloaded source directory.
        chdir($targetDir);


        $buildPrefix = Config::getVersionBuildPrefix( $version );

        if( ! file_exists($buildPrefix) ) {
            mkdir($buildPrefix,0755,true);
        }

        // write variants info.
        $variantInfoFile = $buildPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants';
        $this->logger->debug("Writing variant info to $variantInfoFile");
        file_put_contents($variantInfoFile, serialize($variantInfo));


        // The build object, contains the information to build php.
        $build = new Build;
        $build->setName($name);
        $build->setVersion($version);
        $build->setInstallDirectory($buildPrefix);
        $build->setSourceDirectory($targetDir);


        $builder = new Builder($targetDir, $version);
        $builder->logger = $this->logger;
        $builder->options = $this->options;

        $this->logger->debug( 'Build Directory: ' . realpath($targetDir) );

        foreach( $variantInfo['enabled_variants'] as $name => $value ) {
            $build->enableVariant($name, $value);
        }

        foreach( $variantInfo['disabled_variants'] as $name => $value ) {
            $build->disableVariant($name);
            if($build->hasVariant($name) ) {
                $this->logger->warn("Removing variant $name since we've disabled it from command.");
                $build->removeVariant($name);
            }
        }
        $build->setExtraOptions( $variantInfo['extra_options'] );

        if( $options->clean ) {
            $clean = new CleanTask($this->logger);
            $clean->cleanByVersion($version);
        }

        $buildLogFile = Config::getVersionBuildLogPath( $version );

        // we should only run configure after cleaning files  (?)
        $builder->configure($build);

        $buildTask = new BuildTask($this->logger);
        $buildTask->setLogPath($buildLogFile);
        $buildTask->build();

        if( $options->{'test'} ) {
            $test = new TestTask($this->logger);
            $test->setLogPath($buildLogFile);
            $test->test();
        }

        $install = new InstallTask($this->logger);
        $install->setLogPath($buildLogFile);
        $install->install();

        if( $options->{'post-clean'} ) {
            $clean = new CleanTask($this->logger);
            $clean->cleanByVersion($version);
        }

        /** POST INSTALLATION **/

        /* Check if php.dSYM exists */
        // Fix php.dSYM
        $dSYM = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php.dSYM';
        if ( file_exists($dSYM)) {
            $this->logger->info("---> Moving php.dSYM to php ");
            $php = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
            rename( $dSYM , $php );
        }



        $phpConfigFile = $options->production ? 'php.ini-production' : 'php.ini-development';
        $this->logger->info("---> Copying $phpConfigFile ");
        if( file_exists($phpConfigFile) ) {
            if( ! file_exists( Config::getVersionEtcPath($version) ) )
                mkdir( Config::getVersionEtcPath($version) , 0755 , true );

            $targetConfigPath = Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php.ini';
            if( file_exists($targetConfigPath) ) {
                $this->logger->notice("$targetConfigPath exists, do not overwrite.");
            }
            else {

                // TODO: Move this to PhpConfigPatchTask

                // move config file to target location
                rename( $phpConfigFile , $targetConfigPath );

                // replace current timezone
                $timezone = ini_get('date.timezone');
                $pharReadonly = ini_get('phar.readonly');
                if( $timezone || $pharReadonly ) {
                    // patch default config
                    $content = file_get_contents($targetConfigPath);
                    if( $timezone ) {
                        $this->logger->info("---> Found date.timezone, patch config timezone with $timezone");
                        $content = preg_replace( '/^date.timezone\s*=\s*.*/im', "date.timezone = $timezone" , $content );
                    }
                    if( ! $pharReadonly ) {
                        $this->logger->info("---> Disable phar.readonly option.");
                        $content = preg_replace( '/^phar.readonly\s*=\s*.*/im', "phar.readonly = 0" , $content );
                    }
                    file_put_contents($targetConfigPath, $content);

                }

            }
        }

        $this->logger->info("Source directory: " . $targetDir );

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

