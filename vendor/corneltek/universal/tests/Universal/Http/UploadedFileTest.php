<?php
use Universal\Http\UploadedFile;

class UploadedFileTest extends PHPUnit_Framework_TestCase
{

    public function testValidateExtension()
    {
        $tmpfile = tempnam('/tmp', 'test_');
        $this->assertNotFalse(file_put_contents($tmpfile, 'foo'));
        $filestash = array(
            'name' => 'filename.txt',
            'tmp_name' => $tmpfile,
            'type' => 'text/plain',
            'size' => filesize($tmpfile),
            'error' => 0,
        );
        $file = UploadedFile::createFromArray($filestash);
        $this->assertNotNull($file);
        $this->assertTrue($file->validateExtension(['txt']));
        $this->assertFalse($file->validateExtension(['jpg']));
    }

    public function testGetExtension()
    {
        $tmpfile = tempnam('/tmp', 'test_');
        $this->assertNotFalse(file_put_contents($tmpfile, 'foo'));
        $filestash = array(
            'name' => 'filename.txt',
            'tmp_name' => $tmpfile,
            'type' => 'text/plain',
            'size' => filesize($tmpfile),
            'error' => 0,
        );
        $file = UploadedFile::createFromArray($filestash);
        $this->assertNotNull($file);
        $this->assertEquals('txt', $file->getExtension());
    }


    public function testMove()
    {
        $tmpfile = tempnam('/tmp', 'test_');
        $this->assertNotFalse(file_put_contents($tmpfile, 'foo'));
        $filestash = array( 
            'name' => 'filename.txt',
            'tmp_name' => $tmpfile,
            'type' => 'text/plain',
            'size' => filesize($tmpfile),
            'error' => 0,
        );
        $file = UploadedFile::createFromArray($filestash);
        $this->assertNotNull($file);
        $this->assertInstanceOf('Universal\Http\UploadedFile', $file);
        $ret = $file->moveTo('tests', true);
        $this->assertEquals('tests/filename.txt', $ret);
    }

    /**
     * @covers Universal\Http\UploadedFile
     * @group upload
     * @expectedException Universal\Exception\InvalidUploadFileException
     */
    public function testInvalidUpload()
    {
        $tmpfile = tempnam('/tmp', 'test_');
        $this->assertNotFalse(file_put_contents($tmpfile, 'foo'));
        $filestash = array(
            'name' => 'bar.txt',
            'tmp_name' => $tmpfile,
            'type' => 'text/plain',
            'size' => filesize($tmpfile),
            'error' => 0,
        );
        $file = UploadedFile::createFromArray($filestash);
        $this->assertNotNull($file);
        $this->assertInstanceOf('Universal\Http\UploadedFile', $file);
        $file->moveTo('tests');
    }

    /**
     * @covers Universal\Http\UploadedFile
     * @group upload
     * @expectedException Universal\Exception\UploadErrorException
     */
    public function testUploadError()
    {
        $tmpfile = tempnam('/tmp', 'test_');
        $this->assertNotFalse(file_put_contents($tmpfile, 'foo'));
        $filestash = array(
            'name' => 'foo.txt',
            'tmp_name' => $tmpfile,
            'type' => 'text/plain',
            'size' => filesize($tmpfile),
            'error' => 1,
        );
        $file = UploadedFile::createFromArray($filestash);
        $this->assertNotNull($file);
        $this->assertInstanceOf('Universal\Http\UploadedFile', $file);
        $file->moveTo('tests');
    }
}

