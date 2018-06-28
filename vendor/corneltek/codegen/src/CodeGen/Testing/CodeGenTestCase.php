<?php
namespace CodeGen\Testing;

use CodeGen\Renderable;
use PHPUnit_Framework_TestCase;

abstract class CodeGenTestCase extends PHPUnit_Framework_TestCase
{

    public function assertCodeEquals($expected, Renderable $code, array $args = array())
    {
        $out = $code->render($args);
        static::assertEquals($expected, $out);
    }


    public function assertCodeEqualsFile($fixtureFile, Renderable $code, array $args = array())
    {
        $out = $code->render($args);
        if (!file_exists($fixtureFile) || getenv('OVERRIDE_FIXTURE')) {
            echo "\nGenerating fixture file with below content: $fixtureFile\n";
            echo "======================\n";
            echo $out . "\n";
            echo "======================\n";
            file_put_contents($fixtureFile, $out);
        }
        static::assertStringEqualsFile($fixtureFile, $out, "Testing $fixtureFile");
    }
}



