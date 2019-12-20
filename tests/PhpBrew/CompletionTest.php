<?php

namespace PhpBrew\Tests;

use PhpBrew\Testing\CommandTestCase;

class CompletionTest extends CommandTestCase
{
    /**
     * @dataProvider completionProvider
     */
    public function testCompletion($shell)
    {
        $this->expectOutputString(
            file_get_contents(__DIR__ . '/../../completion/' . $shell . '/_phpbrew')
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
