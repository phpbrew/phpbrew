<?php

namespace PhpBrew;
use PhpBrew\Config;

class UsePhpFunctionWrapper
{
    public static function execute($func, &$output = null, $passthru = false)
    {
        $retVal = false;

        $command = Config::getCurrentPhpBin() . '/php';

        $descriptorspec = array(
           0 => array("pipe", "r"),
           1 => array("pipe", "w"),
           2 => array("pipe", "w")
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if ($passthru) {
            $code = '<?php ' . $func  .'; ?>';
        } else {
            $code = '<?php echo serialize(' . $func  .'); ?>';
        }

        if (is_resource($process)) {
            fwrite($pipes[0], $code);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $retVal = proc_close($process);

            if ($retVal === 0) {
                if (!$passthru) {
                    $output = unserialize($output);
                }

                return true;
            }
        }

        return false;
    }
}
