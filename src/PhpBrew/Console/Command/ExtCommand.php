<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtCommand extends Command
{

    public function usage()
    {
        return "    phpbrew ext [install|enable|disable]";
    }

    public function brief()
    {
        return 'List extensions or execute extension subcommands';
    }

    public function init()
    {
        // $this->registerCommand('enable','PhpBrew\\Command\\ExtCommand\\EnableCommand');
        $this->registerCommand('enable');
        $this->registerCommand('install');
        $this->registerCommand('disable');
    }

    public function execute()
    {
        $php = Config::getCurrentPhpName();
        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';

        // listing all local extensions
        $loaded = array_map( 'strtolower' , get_loaded_extensions());

        $this->logger->info( 'Available extensions:');
        $fp = opendir( $extDir );


        // list for exts not neabled
        $exts = array();
        while( $file = readdir($fp) ) {
            if ( $file === '.' || $file === '..' )
                continue;

            if ( is_file($extDir . DIRECTORY_SEPARATOR . $file) )
                continue;

            $n = strtolower(preg_replace('#-[\d\.]+$#', '', $file));
            if ( in_array($n,$loaded) )
                continue;
            $exts[] = $n;
        }
        sort($loaded);
        sort($exts);

        foreach( $loaded as $ext ) {
            $this->logger->info("  [*] $ext");
        }
        foreach( $exts as $ext ) {
            $this->logger->info("  [ ] $ext");
        }
        closedir($fp);
    }
}





