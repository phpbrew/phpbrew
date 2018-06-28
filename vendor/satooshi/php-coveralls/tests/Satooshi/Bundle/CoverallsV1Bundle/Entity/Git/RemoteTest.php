<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Entity\Git;

/**
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Entity\Git\Remote
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Entity\Coveralls
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class RemoteTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->object = new Remote();
    }

    // getName()

    /**
     * @test
     */
    public function shouldNotHaveRemoteNameOnConstruction()
    {
        $this->assertNull($this->object->getName());
    }

    // getUrl()

    /**
     * @test
     */
    public function shouldNotHaveUrlOnConstruction()
    {
        $this->assertNull($this->object->getUrl());
    }

    // setName()

    /**
     * @test
     */
    public function shouldSetRemoteName()
    {
        $expected = 'remote_name';

        $obj = $this->object->setName($expected);

        $this->assertSame($expected, $this->object->getName());
        $this->assertSame($obj, $this->object);
    }

    // setUrl()

    /**
     * @test
     */
    public function shouldSetRemoteUrl()
    {
        $expected = 'git@github.com:satooshi/php-coveralls.git';

        $obj = $this->object->setUrl($expected);

        $this->assertSame($expected, $this->object->getUrl());
        $this->assertSame($obj, $this->object);
    }

    // toArray()

    /**
     * @test
     */
    public function shouldConvertToArray()
    {
        $expected = array(
            'name' => null,
            'url'  => null,
        );

        $this->assertSame($expected, $this->object->toArray());
        $this->assertSame(json_encode($expected), (string) $this->object);
    }

    /**
     * @test
     */
    public function shouldConvertToFilledArray()
    {
        $name = 'name';
        $url  = 'url';

        $this->object
        ->setName($name)
        ->setUrl($url);

        $expected = array(
            'name' => $name,
            'url'  => $url,
        );

        $this->assertSame($expected, $this->object->toArray());
        $this->assertSame(json_encode($expected), (string) $this->object);
    }
}
