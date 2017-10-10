<?php

namespace PhpBrew;

/**
 * VersionDslParserTest
 *
 * @small
 */
class VersionDslParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var VersionDslParser
     */
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
            array('git@github.com:php/php-src@php-7.1.0RC3', 'https://github.com/php/php-src/archive/php-7.1.0RC3.tar.gz', 'php-7.1.0RC3'), // tag

            // pre-release versions without the github: prefix
            array(
                'php-7.2.0alpha1',
                'https://github.com/php/php-src/archive/php-7.2.0alpha1.tar.gz',
                'php-7.2.0alpha1',
            ),
            array(
                '7.2.0beta2',
                'https://github.com/php/php-src/archive/php-7.2.0beta2.tar.gz',
                'php-7.2.0beta2',
            ),
            array(
                'php-7.2.0RC3',
                'https://github.com/php/php-src/archive/php-7.2.0RC3.tar.gz',
                'php-7.2.0RC3',
            ),

            // github urls
            array('https://www.github.com/php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'),
            array('http://www.github.com/php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'),
            array('www.github.com/php/php-src', 'https://github.com/php/php-src/archive/master.tar.gz', 'php-master'),

            // forks
            array('github:marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'),
            array('github.com:marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'), // implicit branch
            array('git@github.com:marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'),
            array('https://www.github.com/marc/php-src', 'https://github.com/marc/php-src/archive/master.tar.gz', 'php-marc-master'),
            array('git@github.com:marc/php-src@php-7.1.0RC3', 'https://github.com/marc/php-src/archive/php-7.1.0RC3.tar.gz', 'php-marc-7.1.0RC3'), // tag in fork

            // Other URLs
            array('https://www.php.net/~ab/php-7.0.0alpha1.tar.gz', 'https://www.php.net/~ab/php-7.0.0alpha1.tar.gz', 'php-7.0.0alpha1'),
            array('https://www.php.net/~ab/php-7.0.0beta2.tar.gz', 'https://www.php.net/~ab/php-7.0.0beta2.tar.gz', 'php-7.0.0beta2'),
            array('https://www.php.net/~ab/php-7.0.0RC3.tar.gz', 'https://www.php.net/~ab/php-7.0.0RC3.tar.gz', 'php-7.0.0RC3'),
            array('https://www.php.net/~ab/php-7.0.0.tar.gz', 'https://www.php.net/~ab/php-7.0.0.tar.gz', 'php-7.0.0'),
            array('http://php.net/distributions/php-5.6.14.tar.bz2', 'http://php.net/distributions/php-5.6.14.tar.bz2', 'php-5.6.14'),
          );
    }

    /**
     * @dataProvider DslProvider
     */
    public function testGithubDsl($dsl, $url, $version)
    {
        $info = $this->parser->parse($dsl);

        $this->assertSame($version, $info['version']);
        $this->assertSame($url, $info['url']);
    }
}
