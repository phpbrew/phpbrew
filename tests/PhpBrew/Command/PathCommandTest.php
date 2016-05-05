<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class PathCommandTest extends CommandTestCase
{

    public function argumentsProvider() {

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

    public function testUseLatestPHP()
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew use php-{$versionName}");
        // $this->assertRegExp("#php/$versionName/bin\$#", getenv('PHPBREW_PATH'));
        $this->assertEquals("php-$versionName", getenv('PHPBREW_PHP'));
    }

    /**
     * @outputBuffering enabled
     * @dataProvider argumentsProvider
     * @depends testUseLatestPHP
     */
    public function testPathCommand($arg, $pattern) {
        ob_start();
        $this->runCommandWithStdout("phpbrew path $arg");
        $path = ob_get_clean();
        $this->assertRegExp($pattern, $path);
    }
}
