<?php

namespace PhpBrew\Tests\Extension\Provider;

use PhpBrew\Extension\Provider\RepositoryDslParser;
use PHPUnit\Framework\TestCase;

/**
 * ExtensionDslParserTest
 *
 * @small
 * @group extension
 */
class ExtensionDslParserTest extends TestCase
{
    protected $parser;

    public function setUp()
    {
        $this->parser = new RepositoryDslParser();
    }

    public static function dslProvider()
    {
        return array(
            // pecl
            array('xdebug', 'pecl', null, 'xdebug'), // standard pecl package name
            array('APCu', 'pecl', null, 'APCu'), // pecl package name with mixed uppercase/lowercase
            array('com_dotnet', 'pecl', null, 'com_dotnet'), // pecl package name with _
            // github
            array('github:foo/bar', 'github', 'foo', 'bar'), // short github dsl
            array('git@github.com:foo/bar', 'github', 'foo', 'bar'), // long github dsl
            array('http://github.com/foo/bar', 'github', 'foo', 'bar'), // raw http guthub url
            array('https://github.com/foo/bar', 'github', 'foo', 'bar'), // raw https guthub url

            // somebody really likes to type GitHub URLs...
            array('https://www.github.com/foo/bar', 'github', 'foo', 'bar'),
            // bitbucket
            array('bitbucket:foo/bar', 'bitbucket', 'foo', 'bar'), // short bitbucket dsl
            array('git@bitbucket.org:foo/bar', 'bitbucket', 'foo', 'bar'), // long bitbucket dsl
            array('http://bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'), // raw http bitbucket url
            array('https://bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'), // raw https bitbucket url

            // somebody really likes to type BitBuckets URLs...
            array('http://www.bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'),
            // user is feeling luky and finds extension that is not on github or bitbucket
            array('http://luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'), // raw http luky url
            array('https://luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'), // raw https luky url

            // somebody is really luky if this ext compiles...
            array('http://www.luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'),
        );
    }

    /**
     * @dataProvider dslProvider
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
