<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;

/**
 * Task to run `make`
 */
class ConfigureTask extends BaseTask
{

    public $o;

    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function setOptimizationLevel($o)
    {
        $this->o = $o;
    }

    public function build($version)
    {
        $root        = Config::getPhpbrewRoot();
        $buildPrefix = Config::getVersionBuildPrefix( $version );

        if ( ! file_exists('configure') ) {
            $this->debug("configure file not found, running buildconf script...");
            system('./buildconf') !== false or die('buildconf error');
        }

        $cmd = new CommandBuilder('./configure');

        // append cflags
        if ($this->o) {
            $o = $this->o;
            $cflags = getenv('CFLAGS');
            putenv("CFLAGS=$cflags -O$o");
            $_ENV['CFLAGS'] = "$cflags -O$o";
        }

        $args = array();
        $args[] = "--prefix=" . $buildPrefix;
        $args[] = "--with-config-file-path={$buildPrefix}/etc";
        $args[] = "--with-config-file-scan-dir={$buildPrefix}/var/db";
        $args[] = "--with-pear={$buildPrefix}/lib/php";

    }
}
