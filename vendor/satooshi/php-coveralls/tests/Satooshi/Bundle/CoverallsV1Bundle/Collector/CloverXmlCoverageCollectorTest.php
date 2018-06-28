<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Collector;

use Satooshi\Bundle\CoverallsV1Bundle\Entity\JsonFile;
use Satooshi\Bundle\CoverallsV1Bundle\Entity\SourceFile;
use Satooshi\ProjectTestCase;

/**
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Collector\CloverXmlCoverageCollector
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CloverXmlCoverageCollectorTest extends ProjectTestCase
{
    protected function setUp()
    {
        $this->projectDir = realpath(__DIR__ . '/../../../..');

        $this->setUpDir($this->projectDir);

        $this->object = new CloverXmlCoverageCollector();
    }

    protected function createCloverXml()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1365848893">
  <project timestamp="1365848893">
    <file name="%s/test.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="2"/>
    </file>
    <file name="%s/test.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="1"/>
    </file>
    <file name="dummy.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="0"/>
    </file>
    <package name="Hoge">
      <file name="%s/test2.php">
        <class name="TestFile" namespace="Hoge">
          <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
        </class>
        <line num="6" type="method" name="__construct" crap="1" count="0"/>
        <line num="8" type="stmt" count="0"/>
      </file>
      <file path="%s/test3.php" name="test3.php">
        <class name="TestFileBis" namespace="Hoge">
          <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
        </class>
        <line num="6" type="method" name="__construct" crap="1" count="0"/>
        <line num="8" type="stmt" count="0"/>
      </file>
    </package>
  </project>
</coverage>
XML;

        return simplexml_load_string(sprintf($xml, $this->srcDir, $this->srcDir, $this->srcDir, $this->srcDir));
    }

    // getJsonFile()

    /**
     * @test
     */
    public function shouldNotHaveJsonFileOnConstruction()
    {
        $this->assertNull($this->object->getJsonFile());
    }

    // collect() under srcDir

    /**
     * @test
     */
    public function shouldCollect()
    {
        $xml      = $this->createCloverXml();
        $jsonFile = $this->object->collect($xml, $this->srcDir);

        $this->assertSame($jsonFile, $this->object->getJsonFile());
        $this->assertJsonFile($jsonFile, null, null, null, null, '2013-04-13 10:28:13 +0000');

        return $jsonFile;
    }

    /**
     * @test
     * @depends shouldCollect
     */
    public function shouldCollectSourceFiles(JsonFile $jsonFile)
    {
        $sourceFiles = $jsonFile->getSourceFiles();

        $this->assertCount(3, $sourceFiles);

        return $jsonFile;
    }

    /**
     * @test
     * @depends shouldCollectSourceFiles
     */
    public function shouldCollectSourceFileTest1(JsonFile $jsonFile)
    {
        $sourceFiles = $jsonFile->getSourceFiles();

        $name1 = 'test.php';
        $path1 = $this->srcDir . DIRECTORY_SEPARATOR . $name1;

        $this->assertArrayHasKey($path1, $sourceFiles);
        $this->assertSourceFileTest1($sourceFiles[$path1]);
    }

    /**
     * @test
     * @depends shouldCollectSourceFiles
     */
    public function shouldCollectSourceFileTest2(JsonFile $jsonFile)
    {
        $sourceFiles = $jsonFile->getSourceFiles();

        $name2 = 'test2.php';
        $path2 = $this->srcDir . DIRECTORY_SEPARATOR . $name2;

        $this->assertArrayHasKey($path2, $sourceFiles);
        $this->assertSourceFileTest2($sourceFiles[$path2]);
    }

    // collect() under /

    /**
     * @test
     */
    public function shouldCollectUnderRootDir()
    {
        $xml      = $this->createCloverXml();
        $jsonFile = $this->object->collect($xml, DIRECTORY_SEPARATOR);

        $this->assertSame($jsonFile, $this->object->getJsonFile());
        $this->assertJsonFile($jsonFile, null, null, null, null, '2013-04-13 10:28:13 +0000');

        return $jsonFile;
    }

    /**
     * @test
     * @depends shouldCollectUnderRootDir
     */
    public function shouldCollectSourceFilesUnderRootDir(JsonFile $jsonFile)
    {
        $sourceFiles = $jsonFile->getSourceFiles();

        $this->assertCount(3, $sourceFiles);

        return $jsonFile;
    }

    /**
     * @test
     * @depends shouldCollectSourceFilesUnderRootDir
     */
    public function shouldCollectSourceFileTest1UnderRootDir(JsonFile $jsonFile)
    {
        $sourceFiles = $jsonFile->getSourceFiles();

        $name1 = 'test.php';
        $path1 = $this->srcDir . DIRECTORY_SEPARATOR . $name1;

        $this->assertArrayHasKey($path1, $sourceFiles);
        $this->assertSourceFileTest1UnderRootDir($sourceFiles[$path1]);
    }

    /**
     * @test
     * @depends shouldCollectSourceFilesUnderRootDir
     */
    public function shouldCollectSourceFileTest2UnderRootDir(JsonFile $jsonFile)
    {
        $sourceFiles = $jsonFile->getSourceFiles();

        $name2 = 'test2.php';
        $path2 = $this->srcDir . DIRECTORY_SEPARATOR . $name2;

        $this->assertArrayHasKey($path2, $sourceFiles);
        $this->assertSourceFileTest2UnderRootDir($sourceFiles[$path2]);
    }

    // custom assert

    protected function assertJsonFile($jsonFile, $serviceName, $serviceJobId, $repoToken, $git, $runAt)
    {
        $this->assertSame($serviceName, $jsonFile->getServiceName());
        $this->assertSame($serviceJobId, $jsonFile->getServiceJobId());
        $this->assertSame($repoToken, $jsonFile->getRepoToken());
        $this->assertSame($git, $jsonFile->getGit());
        $this->assertSame($runAt, $jsonFile->getRunAt());
    }

    protected function assertSourceFile(SourceFile $sourceFile, $name, $path, $fileLines, array $coverage, $source)
    {
        $this->assertSame($name, $sourceFile->getName());
        $this->assertSame($path, $sourceFile->getPath());
        $this->assertSame($fileLines, $sourceFile->getFileLines());
        $this->assertSame($coverage, $sourceFile->getCoverage());
        $this->assertSame($source, $sourceFile->getSource());
    }

    protected function assertSourceFileTest1(SourceFile $sourceFile)
    {
        $name1        = 'test.php';
        $path1        = $this->srcDir . DIRECTORY_SEPARATOR . $name1;
        $fileLines1   = 9;
        $coverage1    = array_fill(0, $fileLines1, null);
        $coverage1[6] = 3;
        $source1      = trim(file_get_contents($path1));

        $this->assertSourceFile($sourceFile, $name1, $path1, $fileLines1, $coverage1, $source1);
    }

    protected function assertSourceFileTest2(SourceFile $sourceFile)
    {
        $name2        = 'test2.php';
        $path2        = $this->srcDir . DIRECTORY_SEPARATOR . $name2;
        $fileLines2   = 10;
        $coverage2    = array_fill(0, $fileLines2, null);
        $coverage2[7] = 0;
        $source2      = trim(file_get_contents($path2));

        $this->assertSourceFile($sourceFile, $name2, $path2, $fileLines2, $coverage2, $source2);
    }

    protected function assertSourceFileTest1UnderRootDir(SourceFile $sourceFile)
    {
        $name1        = 'test.php';
        $path1        = $this->srcDir . DIRECTORY_SEPARATOR . $name1;
        $fileLines1   = 9;
        $coverage1    = array_fill(0, $fileLines1, null);
        $coverage1[6] = 3;
        $source1      = trim(file_get_contents($path1));

        $this->assertSourceFile($sourceFile, $path1, $path1, $fileLines1, $coverage1, $source1);
    }

    protected function assertSourceFileTest2UnderRootDir(SourceFile $sourceFile)
    {
        $name2        = 'test2.php';
        $path2        = $this->srcDir . DIRECTORY_SEPARATOR . $name2;
        $fileLines2   = 10;
        $coverage2    = array_fill(0, $fileLines2, null);
        $coverage2[7] = 0;
        $source2      = trim(file_get_contents($path2));

        $this->assertSourceFile($sourceFile, $path2, $path2, $fileLines2, $coverage2, $source2);
    }
}
