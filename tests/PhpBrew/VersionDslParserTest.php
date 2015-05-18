<?php

namespace PhpBrew;

/**
 * VersionDslParserTest
 *
 * @small
 */
class ExtensionDslParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    public function setUp()
    {
        $this->parser = new VersionDslParser;
    }

    public function DslProvider()
    {
        return array(
            // official
            array('github:php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'), // implicit branch
            array('github:php/php-src@branch', 'https://github.com/php/php-src/archive/branch.tar.gz', 'php-branch'), // explicit branch
            array('github.com:php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'), // implicit branch
            array('github.com:php/php-src@branch', 'https://github.com/php/php-src/archive/branch.tar.gz', 'php-branch'), // explicit branch
            array('git@github.com:php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'), // implicit branch
            array('git@github.com:php/php-src@branch', 'https://github.com/php/php-src/archive/branch.tar.gz', 'php-branch'), // explicit branch

            // github urls
            array('https://www.github.com/php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'),
            array('http://www.github.com/php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'),
            array('www.github.com/php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'),

            // forks
            array('github:marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'),
            array('github.com:marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'), // implicit branch
            array('git@github.com:marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'),
            array('https://www.github.com/marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'),
        );
    }

    /**
     * @dataProvider DslProvider
     */
    public function testGithubDsl($dsl, $url, $version)
    {
        $this->assertSame(
            array(
                'version' => $version,
                'url' => $url,
            ),
            $this->parser->parse($dsl)
        );
    }
}
