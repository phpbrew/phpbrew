<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Entity\Git;

/**
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Entity\Git\Commit
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Entity\Coveralls
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CommitTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->object = new Commit();
    }

    // getId()

    /**
     * @test
     */
    public function shouldNotHaveIdOnConstruction()
    {
        $this->assertNull($this->object->getId());
    }

    // getAuthorName()

    /**
     * @test
     */
    public function shouldNotHoveAuthorNameOnConstruction()
    {
        $this->assertNull($this->object->getAuthorName());
    }

    // getAuthorEmail()

    /**
     * @test
     */
    public function shouldNotHoveAuthorEmailOnConstruction()
    {
        $this->assertNull($this->object->getAuthorEmail());
    }

    // getCommitterName()

    /**
     * @test
     */
    public function shouldNotHoveCommitterNameOnConstruction()
    {
        $this->assertNull($this->object->getCommitterName());
    }

    // getCommitterEmail()

    /**
     * @test
     */
    public function shouldNotHoveCommitterEmailOnConstruction()
    {
        $this->assertNull($this->object->getCommitterEmail());
    }

    // getMessage()

    /**
     * @test
     */
    public function shouldNotHoveMessageOnConstruction()
    {
        $this->assertNull($this->object->getMessage());
    }

    // setId()

    /**
     * @test
     */
    public function shouldSetId()
    {
        $expected = 'id';

        $obj = $this->object->setId($expected);

        $this->assertSame($expected, $this->object->getId());
        $this->assertSame($obj, $this->object);
    }

    // setAuthorName()

    /**
     * @test
     */
    public function shouldSetAuthorName()
    {
        $expected = 'author_name';

        $obj = $this->object->setAuthorName($expected);

        $this->assertSame($expected, $this->object->getAuthorName());
        $this->assertSame($obj, $this->object);
    }

    // setAuthorEmail()

    /**
     * @test
     */
    public function shouldSetAuthorEmail()
    {
        $expected = 'author_email';

        $obj = $this->object->setAuthorEmail($expected);

        $this->assertSame($expected, $this->object->getAuthorEmail());
        $this->assertSame($obj, $this->object);
    }

    // setCommitterName()

    /**
     * @test
     */
    public function shouldSetCommitterName()
    {
        $expected = 'committer_name';

        $obj = $this->object->setCommitterName($expected);

        $this->assertSame($expected, $this->object->getCommitterName());
        $this->assertSame($obj, $this->object);
    }

    // setCommitterEmail()

    /**
     * @test
     */
    public function shouldSetCommitterEmail()
    {
        $expected = 'committer_email';

        $obj = $this->object->setCommitterEmail($expected);

        $this->assertSame($expected, $this->object->getCommitterEmail());
        $this->assertSame($obj, $this->object);
    }

    // setMessage()

    /**
     * @test
     */
    public function shouldSetMessage()
    {
        $expected = 'message';

        $obj = $this->object->setMessage($expected);

        $this->assertSame($expected, $this->object->getMessage());
        $this->assertSame($obj, $this->object);
    }

    // toArray()

    /**
     * @test
     */
    public function shouldConvertToArray()
    {
        $expected = array(
            'id'              => null,
            'author_name'     => null,
            'author_email'    => null,
            'committer_name'  => null,
            'committer_email' => null,
            'message'         => null,
        );

        $this->assertSame($expected, $this->object->toArray());
        $this->assertSame(json_encode($expected), (string) $this->object);
    }

    /**
     * @test
     */
    public function shouldConvertToFilledArray()
    {
        $id             = 'id';
        $authorName     = 'author_name';
        $authorEmail    = 'author_email';
        $committerName  = 'committer_name';
        $committerEmail = 'committer_email';
        $message        = 'message';

        $this->object
        ->setId($id)
        ->setAuthorName($authorName)
        ->setAuthorEmail($authorEmail)
        ->setCommitterName($committerName)
        ->setCommitterEmail($committerEmail)
        ->setMessage($message);

        $expected = array(
            'id'              => $id,
            'author_name'     => $authorName,
            'author_email'    => $authorEmail,
            'committer_name'  => $committerName,
            'committer_email' => $committerEmail,
            'message'         => $message,
        );

        $this->assertSame($expected, $this->object->toArray());
        $this->assertSame(json_encode($expected), (string) $this->object);
    }
}
