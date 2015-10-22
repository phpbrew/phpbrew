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
        $this->assertSame(
            array(
                'version' => $version,
                'url' => $url,
            ),
            $this->parser->parse($dsl)
        );
    }
}
