<?php

namespace PhpBrew\Extension\Provider;

/**
 * ExtensionDslParserTest
 *
 * @small
 * @group extension
 */
class ExtensionDslParserTest extends \\PHPUnit\Framework\TestCase
{
    protected $parser;

    public function setUp()
    {
        $this->parser = new RepositoryDslParser;
    }

    public function DslProvider()
    {
        return array(
            // pecl
            array('xdebug', 'pecl', null, 'xdebug'), // standard pecl package namce
            array('APCu', 'pecl', null, 'APCu'), // pecl package name with mixed uppercase/lowercase
            array('com_dotnet', 'pecl', null, 'com_dotnet'), // pecl package name with _
            // github
            array('github:foo/bar', 'github', 'foo', 'bar'), // short github dsl
            array('git@github.com:foo/bar', 'github', 'foo', 'bar'), // long github dsl
            array('http://github.com/foo/bar', 'github', 'foo', 'bar'), // raw http guthub url
            array('https://github.com/foo/bar', 'github', 'foo', 'bar'), // raw https guthub url
            array('https://www.github.com/foo/bar', 'github', 'foo', 'bar'), // somebody really likes to type githubs urls...
            // bitbucket
            array('bitbucket:foo/bar', 'bitbucket', 'foo', 'bar'), // short bitbucket dsl
            array('git@bitbucket.org:foo/bar', 'bitbucket', 'foo', 'bar'), // long bitbucket dsl
            array('http://bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'), // raw http bitbucket url
            array('https://bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'), // raw https bitbucket url
            array('http://www.bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'), // somebody really likes to type bitbuckets urls...
            // user is feeling luky and finds extension that is not on github or bitbucket
            array('http://luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'), // raw http luky url
            array('https://luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'), // raw https luky url
            array('http://www.luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'), // somebody is really luky if this ext compiles...
        );
    }

    /**
     * @dataProvider DslProvider
     */
    public function testGithubDsl($dsl, $repo, $owner, $package)
    {
        $this->assertSame(
            array(
                'repository' => $repo,
                'owner' => $owner,
                'package' => $package
            ),
            $this->parser->parse($dsl)
        );
    }
}
