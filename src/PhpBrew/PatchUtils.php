<?php
namespace PhpBrew;

use RuntimeException;

class PatchUtils
{
    public static function apply($originalFile, $patchFile, $level = 0)
    {
        $lastline = system("patch -p{$level} $originalFile $patchFile", $ret);
        return $ret == 0;
    }

    public static function applyStdin($diff, &$output, $level = 0)
    {
        $desc = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        );
        $process = proc_open("patch", $desc, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException("Can't open process");
        }
        fwrite($pipes[0], $diff);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        return proc_close($process);
    }

    public static function applyFileStdin($originalFile, $diff, &$output, $level = 0)
    {
        $desc = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        );
        $process = proc_open("patch $originalFile", $desc, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException("Can't open process");
        }
        fwrite($pipes[0], $diff);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        return proc_close($process);
    }

    public static function reinplace($file, $pattern, $replace)
    {
        $content = file_get_contents($file);
        $ret = preg_replace($pattern, $replace, $content);
        file_put_contents($file, $content);
        return $ret;
    }
}
