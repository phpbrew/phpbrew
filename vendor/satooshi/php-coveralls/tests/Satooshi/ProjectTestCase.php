<?php

namespace Satooshi;

class ProjectTestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUpDir($projectDir)
    {
        $this->rootDir       = realpath($projectDir . '/prj');
        $this->srcDir        = realpath($this->rootDir . '/files');

        $this->url           = 'https://coveralls.io/api/v1/jobs';
        $this->filename      = 'json_file';

        // build
        $this->buildDir       = $this->rootDir . '/build';
        $this->logsDir        = $this->rootDir . '/build/logs';

        // log
        $this->cloverXmlPath  = $this->logsDir . '/clover.xml';
        $this->cloverXmlPath1 = $this->logsDir . '/clover-part1.xml';
        $this->cloverXmlPath2 = $this->logsDir . '/clover-part2.xml';
        $this->jsonPath       = $this->logsDir . '/coveralls-upload.json';
    }

    protected function makeProjectDir($srcDir = null, $logsDir = null, $cloverXmlPaths = null, $logsDirUnwritable = false, $jsonPathUnwritable = false)
    {
        if ($srcDir !== null && !is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }

        if ($logsDir !== null && !is_dir($logsDir)) {
            mkdir($logsDir, 0777, true);
        }

        if ($cloverXmlPaths !== null) {
            if (is_array($cloverXmlPaths)) {
                foreach ($cloverXmlPaths as $cloverXmlPath) {
                    touch($cloverXmlPath);
                }
            } else {
                touch($cloverXmlPaths);
            }
        }

        if ($logsDirUnwritable) {
            if (file_exists($logsDir)) {
                chmod($logsDir, 0577);
            }
        }

        if ($jsonPathUnwritable) {
            touch($this->jsonPath);
            chmod($this->jsonPath, 0577);
        }
    }

    protected function rmFile($file)
    {
        if (is_file($file)) {
            chmod(dirname($file), 0777);
            unlink($file);
        }
    }

    protected function rmDir($dir)
    {
        if (is_dir($dir)) {
            chmod($dir, 0777);
            rmdir($dir);
        }
    }
}
