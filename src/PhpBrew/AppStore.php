<?php
namespace PhpBrew;

class AppStore
{
    public static function all() 
    {
        $apps = array(
            'composer'  => array('url' => 'https://getcomposer.org/composer.phar', 'as'                              => 'composer'),
            'phpunit'   => array('url' => 'https://phar.phpunit.de/phpunit.phar', 'as'                               => 'phpunit'),
            'phpmd'     => array('url' => 'http://static.phpmd.org/php/latest/phpmd.phar', 'as'                      => 'phpmd'),
            'behat-2.5' => array('url' => 'https://github.com/Behat/Behat/releases/download/v2.5.5/behat.phar', 'as' => 'behat'),
            'behat-3.5' => array('url' => 'https://github.com/Behat/Behat/releases/download/v3.0.15/behat.phar', 'as' => 'behat'),
            'sami'      => array('url' => 'http://get.sensiolabs.org/sami.phar', 'as' => 'sami'),
            'phpcs'     => array('url' => 'https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar', 'as' => 'phpcs'),
        );
        $phpunitapps = explode(' ','phpunit phpcov phpcpd phpdcd phptok phploc');
        foreach ($phpunitapps as $phpunitapp) {
            $apps[ $phpunitapp ] = array(
                'url' => sprintf('https://phar.phpunit.de/%s.phar', $phpunitapp),
                'as' => $phpunitapp,
            );
        }
        return $apps;
    }
}






