<?php

namespace PHPBrew\Tests;

use PHPBrew\Testing\CommandTestCase;

class CompletionTest extends CommandTestCase
{
    /**
     * @dataProvider completionProvider
     */
    public function testCompletion($shell)
    {
        $this->expectOutputString(
            file_get_contents('completion/' . $shell . '/_phpbrew')
        );

        $this->app->run(array('phpbrew', $shell, '--bind', 'phpbrew', '--program', 'phpbrew'));
    }

    public static function completionProvider()
    {
        return array(
            'bash' => array('bash'),
            'zsh' => array('zsh'),
        );
    }
}
