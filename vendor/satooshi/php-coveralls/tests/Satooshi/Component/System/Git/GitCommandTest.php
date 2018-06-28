<?php

namespace Satooshi\Component\System\Git;

/**
 * @covers Satooshi\Component\System\Git\GitCommand
 * @covers Satooshi\Component\System\SystemCommand
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class GitCommandTest extends \PHPUnit_Framework_TestCase
{
    protected function createGitCommandMock($params)
    {
        $class = 'Satooshi\Component\System\Git\GitCommand';
        $adapter = $this->getMock($class, array('executeCommand'));

        $adapter
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->equalTo($params));

        return $adapter;
    }

    // getCommandPath()

    /**
     * @test
     */
    public function shouldBeGitCommand()
    {
        $object = new GitCommand();

        $expected = 'git';

        $this->assertSame($expected, $object->getCommandPath());
    }

    // getBranches()
    //

    /**
     * @test
     */
    public function shouldExecuteGitBranchCommand()
    {
        $expected = 'git branch';

        $object = $this->createGitCommandMock($expected);
        $object->getBranches();
    }

    /**
     * @test
     */
    public function shouldReturnBranches()
    {
        $object = new GitCommand();
        $actual = $object->getBranches();

        $this->assertTrue(is_array($actual));
        $this->assertNotEmpty($actual);
    }

    // getHeadCommit()

    /**
     * @test
     */
    public function shouldExecuteGitLogCommand()
    {
        $expected = "git log -1 --pretty=format:'%H%n%aN%n%ae%n%cN%n%ce%n%s'";

        $object = $this->createGitCommandMock($expected);
        $object->getHeadCommit();
    }

    /**
     * @test
     */
    public function shouldReturnHeadCommit()
    {
        $object = new GitCommand();
        $actual = $object->getHeadCommit();

        $this->assertTrue(is_array($actual));
        $this->assertNotEmpty($actual);
        $this->assertCount(6, $actual);
    }

    // getRemotes()

    /**
     * @test
     */
    public function shouldExecuteGitRemoteCommand()
    {
        $expected = 'git remote -v';

        $object = $this->createGitCommandMock($expected);
        $object->getRemotes();
    }

    /**
     * @test
     */
    public function shouldReturnRemotes()
    {
        $object = new GitCommand();
        $actual = $object->getRemotes();

        $this->assertTrue(is_array($actual));
        $this->assertNotEmpty($actual);
    }

    // execute()

    /**
     * @test
     * @expectedException RuntimeException
     */
    public function throwRuntimeExceptionIfExecutedWithoutArgs()
    {
        // `git` return 1 and cause RuntimeException
        $object = new GitCommand();
        $object->execute();
    }

    // createCommand()

    /**
     * @test
     */
    public function shouldCreateCommand()
    {
        $object = new GitCommand();
        $object->setCommandPath('ls');

        $actual = $object->execute();

        $this->assertTrue(is_array($actual));
        $this->assertNotEmpty($actual);
    }
}
