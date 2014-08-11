<?php

namespace PhpBrew;

use PhpBrew\Console;

class PhpBrew
{
    public function run($args, $version, $buffer = false) {
        if($buffer) ob_start();
        $this->runUse($version);
        $console = new Console;
        array_unshift($args, 'phpbrew');
        $console->run($args);
        if($buffer) return ob_end_clean();
    }

    public static function getCleanPath()
    {
        $PATH = getenv('PATH');
        return preg_replace('#(^|:).+\.phpbrew/php/php-(\d+\.*){3}/bin:#', '', $PATH);
    }

    public static function runUse($version)
    {
        putenv("PHPBREW_BIN=". Config::getPhpbrewHome() . '/.phpbrew/bin');
        putenv("PHPBREW_HOME=" . Config::getPhpbrewHome());
        putenv("PHPBREW_LOOKUP_PREFIX=/usr/local/Cellar:/usr/local");
        putenv("PHPBREW_PATH=" . Config::getPhpbrewHome() ."/php/php-{$version}/bin");
        putenv("PHPBREW_PHP=php-{$version}");
        putenv("PHPBREW_ROOT=" . Config::getPhpbrewRoot());
        putenv('PATH=' . getenv('PHPBREW_PATH') . ':' . self::getCleanPath());
    }

}