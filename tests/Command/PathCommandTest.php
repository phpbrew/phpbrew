<?php

namespace PHPBrew\Tests\Command;

use PHPBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class PathCommandTest extends CommandTestCase
{

    public function argumentsProvider()
    {
        return array(
            array("build",   "#\.phpbrew/build/.+#"),
            array("ext-src", "#\.phpbrew/build/.+/ext$#"),
            array("include", "#\.phpbrew/php/.+/include$#"),
            array("etc",     "#\.phpbrew/php/.+/etc$#"),
            array("dist",    "#\.phpbrew/distfiles$#"),
            array("root",    "#\.phpbrew$#"),
            array("home",    "#\.phpbrew$#"),
        );
    }

    /**
     * @outputBuffering enabled
     * @dataProvider argumentsProvider
     */
    public function testPathCommand($arg, $pattern)
    {
        putenv('PHPBREW_PHP=7.4.0');

        ob_start();
        $this->runCommandWithStdout("phpbrew path $arg");
        $path = ob_get_clean();
        $this->assertRegExp($pattern, $path);
    }
}
