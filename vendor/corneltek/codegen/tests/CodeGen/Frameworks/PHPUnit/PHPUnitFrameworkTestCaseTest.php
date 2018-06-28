<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\LicenseBlock;
use CodeGen\Raw;
use CodeGen\Frameworks\PHPUnit\PHPUnitFrameworkTestCase;
use CodeGen\Frameworks\PHPUnit\Assertions;

class PHPUnitFrameworkTestCaseTest extends CodeGenTestCase
{
    public function testGeneratingTestCase()
    {
        $testCase = new PHPUnitFrameworkTestCase('My App Test');
        $testCase->addTest('arrayIsNotEmpty');
        $this->assertCodeEqualsFile('tests/data/frameworks/phpunit/phpunit_testcase.fixture', $testCase);
    }

    public function testTestCaseWithNamespace()
    {
        $testCase = new PHPUnitFrameworkTestCase('My App Ns Test');
        $testCase->in('SomeNamespace');
        $testCase->addTest('arrayIsNotEmpty');
        $this->assertCodeEqualsFile('tests/data/frameworks/phpunit/phpunit_testcase_ns.fixture', $testCase);
    }

    public function testGeneratingTestCaseWithAssertions()
    {
        $testCase = new PHPUnitFrameworkTestCase('MyAppSimpleTest');
        $method = $testCase->addTest('simple');
        $method[] = Assertions::assertEquals(10, 10);
        $method[] = Assertions::assertEquals(10, new Raw(10));
        $this->assertCodeEqualsFile('tests/data/frameworks/phpunit/phpunit_testcase_simple.fixture', $testCase);
    }

    public function testGeneratingTestCaseWithSentence()
    {
        $testCase = new PHPUnitFrameworkTestCase('What a simple test');
        $method = $testCase->addTest('simple');
        $this->assertCodeEqualsFile('tests/data/frameworks/phpunit/phpunit_testcase_title.fixture', $testCase);
    }

}

