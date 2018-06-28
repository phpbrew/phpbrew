<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Collector;

use Satooshi\Bundle\CoverallsV1Bundle\Entity\JsonFile;
use Satooshi\Bundle\CoverallsV1Bundle\Entity\SourceFile;

/**
 * Coverage collector for clover.xml.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CloverXmlCoverageCollector
{
    /**
     * JsonFile.
     *
     * @var \Satooshi\Bundle\CoverallsV1Bundle\Entity\JsonFile
     */
    protected $jsonFile;

    // API

    /**
     * Collect coverage from XML object.
     *
     * @param \SimpleXMLElement $xml     Clover XML object.
     * @param string            $rootDir Path to repository root directory.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Entity\JsonFile
     */
    public function collect(\SimpleXMLElement $xml, $rootDir)
    {
        $root = rtrim($rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!isset($this->jsonFile)) {
            $this->jsonFile = new JsonFile();
        }

        // overwrite if run_at has already been set
        $runAt = $this->collectRunAt($xml);
        $this->jsonFile->setRunAt($runAt);

        $xpaths = array(
            '/coverage/project/file',
            '/coverage/project/package/file',
        );

        foreach ($xpaths as $xpath) {
            foreach ($xml->xpath($xpath) as $file) {
                $srcFile = $this->collectFileCoverage($file, $root);

                if ($srcFile !== null) {
                    $this->jsonFile->addSourceFile($srcFile);
                }
            }
        }

        return $this->jsonFile;
    }

    // Internal method

    /**
     * Collect timestamp when the job ran.
     *
     * @param SimpleXMLElement $xml    Clover XML object of a file.
     * @param string           $format DateTime format.
     *
     * @return string
     */
    protected function collectRunAt(\SimpleXMLElement $xml, $format = 'Y-m-d H:i:s O')
    {
        $timestamp = $xml->project['timestamp'];
        $runAt     = new \DateTime('@' . $timestamp);

        return $runAt->format($format);
    }

    /**
     * Collect coverage data of a file.
     *
     * @param SimpleXMLElement $file Clover XML object of a file.
     * @param string           $root Path to src directory.
     *
     * @return null|\Satooshi\Bundle\CoverallsV1Bundle\Entity\SourceFile
     */
    protected function collectFileCoverage(\SimpleXMLElement $file, $root)
    {
        $absolutePath = realpath((string) ($file['path'] ?: $file['name']));

        if (false === strpos($absolutePath, $root)) {
            return;
        }

        if ($root !== DIRECTORY_SEPARATOR) {
            $filename = str_replace($root, '', $absolutePath);
        } else {
            $filename = $absolutePath;
        }

        return $this->collectCoverage($file, $absolutePath, $filename);
    }

    /**
     * Collect coverage data.
     *
     * @param SimpleXMLElement $file     Clover XML object of a file.
     * @param string           $path     Path to source file.
     * @param string           $filename Filename.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Entity\SourceFile
     */
    protected function collectCoverage(\SimpleXMLElement $file, $path, $filename)
    {
        if ($this->jsonFile->hasSourceFile($path)) {
            $srcFile = $this->jsonFile->getSourceFile($path);
        } else {
            $srcFile = new SourceFile($path, $filename);
        }

        foreach ($file->line as $line) {
            if ((string) $line['type'] === 'stmt') {
                $lineNum = (int) $line['num'];

                if ($lineNum > 0) {
                    $srcFile->addCoverage($lineNum - 1, (int) $line['count']);
                }
            }
        }

        return $srcFile;
    }

    // accessor

    /**
     * Return json file.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Entity\JsonFile
     */
    public function getJsonFile()
    {
        return $this->jsonFile;
    }
}
