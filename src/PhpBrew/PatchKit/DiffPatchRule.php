<?php

namespace PhpBrew\PatchKit;

use CLIFramework\Logger;
use PhpBrew\Buildable;

/**
 * DiffPatchRule implements a diff based patch rule.
 */
final class DiffPatchRule implements PatchRule
{
    /**
     * @var string
     */
    private $patch;

    /**
     * @var int
     */
    private $strip = 0;

    private function __construct()
    {
    }

    /**
     * @param int $level
     *
     * @return $this
     */
    public function strip($level)
    {
        $this->strip = $level;

        return $this;
    }

    /**
     * @param string $patch The path contents
     */
    public static function fromPatch($patch)
    {
        $instance = new self();
        $instance->patch = $patch;

        return $instance;
    }

    public function backup(Buildable $build, Logger $logger)
    {
    }

    public function apply(Buildable $build, Logger $logger)
    {
        $logger->info('---> Applying patch...');

        $process = proc_open(
            sprintf('patch --forward --backup -p%d', $this->strip),
            array(
                array('pipe', 'r'),
                array('pipe', 'w'),
                array('pipe', 'w'),
            ),
            $pipes,
            $build->getSourceDirectory()
        );

        if (!fwrite($pipes[0], $this->patch)) {
            return 0;
        }

        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);

        if ($output !== '') {
            $logger->info($output);
        }

        $error = stream_get_contents($pipes[2]);

        if ($error !== '') {
            $logger->error($error);
        }

        if (proc_close($process) !== 0) {
            $logger->error('Patch failed');

            return 0;
        }

        $logger->info('Done.');

        return 1;
    }
}
