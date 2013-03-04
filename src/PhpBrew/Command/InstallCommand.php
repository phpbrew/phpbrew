<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\Variants;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;

use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\Tasks\InstallTask;



class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

    public function usage() {
        return 'phpbrew install [php-version] ([+variant...])';
    }

    public function options($opts)
    {
        $opts->add('test','tests');
        $opts->add('no-clean','Do not clean object files before building.');
        $opts->add('post-clean','Run make clean after building PHP.');
        $opts->add('production','Use production configuration');
        $opts->add('n|nice:', 'process nice level');
        $opts->add('patch:',  'apply patch before build');
        $opts->add('old','install old phps (less than 5.3)');
        $opts->add('f|force','force');
    }

    public function execute($version)
    {
        $options = $this->options;
        $logger = $this->logger;

        // get extra options for building php
        $extra = array();
        $args = func_get_args();
        array_shift($args);

        // split variant strings
        $isVariant = true;
        $tmp = array();
        foreach( $args as $a ) {
            if( $a == '--' ) {
                $isVariant = false;
                continue;
            }

            if( $isVariant ) {
                $a = array_filter(explode('+',$a), function($a) { return $a ? true : false; });
                $tmp = array_merge( $tmp , $a );
            }
            else {
                $extra[] = $a;
            }
        }
        $args = $tmp;

        $info = PhpSource::getVersionInfo( $version, $this->options->old );
        if( ! $info)
            throw new Exception("Version $version not found.");

        $prepare = new PrepareDirectoryTask($this->logger);
        $prepare->prepareForVersion($version);

        $home = Config::getPhpbrewRoot();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getVersionBuildPrefix( $version );

        // convert patch to realpath
        if( $this->options->patch ) {
            $patch = realpath($this->options->patch);
            // rewrite patch path
            $this->options->keys['patch']->value = $patch;
        }

        chdir( $buildDir );

        $download = new DownloadTask($this->logger);
        $targetDir = $download->downloadByVersionString($version, $this->options->old , $this->options->force );

        if( ! file_exists($targetDir ) ) {
            throw new Exception("Download failed.");
        }

        $builder = new \PhpBrew\Builder( $targetDir, $version );
        $builder->logger = $logger;
        $builder->options = $options;

        $logger->info( 'Build Directory: ' . realpath($buildDir . DIRECTORY_SEPARATOR . $targetDir) );

        // strip plus sign.
        foreach( $args as $a ) {
            $a = preg_replace( '/^\+/', '', $a );
            $builder->addVariant( $a );
        }

        if( ! $options->{'no-clean'} ) {
            $clean = new CleanTask($this->logger);
            $clean->cleanByVersion($version);
        }

        $builder->configure( $extra );

        $build = new BuildTask($this->logger);
        $build->setLogPath(Config::getVersionBuildLogPath( $version ));
        $build->build();


        if( $options->{'test'} ) {
            $logger->info("Testing");
            $cmd = new CommandBuilder('make test');
            if( $options->nice )
                $cmd->nice( $options->nice );
            $cmd->append = true;
            $cmd->stdout = Config::getVersionBuildLogPath( $version );
            $logger->debug( '' .  $cmd  );
            $cmd->execute() !== false or die('Test failed.');
        }


        $install = new InstallTask($this->logger);
        $install->setLogPath(Config::getVersionBuildLogPath( $version ));
        $install->install();

        if( $options->{'post-clean'} ) {
            $clean = new CleanTask($this->logger);
            $clean->cleanByVersion($version);
        }

        /** POST INSTALLATION **/

        /* Check if php.dSYM exists */
        $dSYM = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php.dSYM';
        if ( file_exists($dSYM)) {
            $logger->info("---> Moving php.dSYM to php ");
            $php = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
            rename( $dSYM , $php );
        }



        $phpConfigFile = $options->production ? 'php.ini-production' : 'php.ini-development';
        $logger->info("---> Copying $phpConfigFile ");
        if( file_exists($phpConfigFile) ) {
            if( ! file_exists( Config::getVersionEtcPath($version) ) )
                mkdir( Config::getVersionEtcPath($version) , 0755 , true );

            $targetConfigPath = Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php.ini';
            if( file_exists($targetConfigPath) ) {
                $logger->notice("$targetConfigPath exists, do not overwrite.");
            }
            else {
                // move config file to target location
                rename( $phpConfigFile , $targetConfigPath );

                // replace current timezone
                $timezone = ini_get('date.timezone');
                $pharReadonly = ini_get('phar.readonly');
                if( $timezone || $pharReadonly ) {
                    // patch default config
                    $content = file_get_contents($targetConfigPath);

                    if( $timezone ) {
                        $logger->info("Found date.timezone, patch config timezone with $timezone");
                        $content = preg_replace( '/^date.timezone\s+=\s+.*/im', "date.timezone = $timezone" , $content );
                    }
                    if( ! $pharReadonly ) {
                        $logger->info("Disable phar.readonly option.");
                        $content = preg_replace( '/^phar.readonly\s+=\s+.*/im', "phar.readonly = 0" , $content );
                    }
                    file_put_contents($targetConfigPath, $content);

                }

            }
        }

        $logger->info("Source directory: " . realpath( $targetDir ) );

        $logger->info("Done!");

        echo <<<EOT
To use the newly built PHP, try the line(s) below:

    $ phpbrew use $version

Or you can use switch command to switch your default php version to $version:

    $ phpbrew switch $version

Enjoy!
EOT;

    }
}

