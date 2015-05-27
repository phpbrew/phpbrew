<?php

namespace PhpBrew;

use Exception;

class VersionDslParser
{
    protected static $schemes = array(
        'git@github.com:',
        'github.com:',
        'github.com/',
        'github:',
    );

    public function parse($dsl)
    {
        $result = false;

        // make url
        $url = str_replace(self::$schemes, 'https://github.com/', $dsl);

        // parse github fork owner and branch
        if (preg_match("#https?://(www\.)?github\.com/([0-9a-zA-Z-._]+)/php-src(@([0-9a-zA-Z-._]+))?#", $url, $matches)) {
            $owner = $matches[2];
            $branch = isset($matches[4]) ? $matches[4] : 'master';
            $result = array(
                'version' => ($owner === 'php' ? "php-{$branch}" : "php-{$owner}-{$branch}"),
                'url' => "https://github.com/{$owner}/php-src/archive/{$branch}.tar.gz"
            );
        }

        // non github url
        if(! $result && preg_match('#^https?://#', $url)) {
            if (! preg_match('#(php-(\d.\d+.\d+)\.tar\.(?:gz|bz2))#',$url, $matches)) {
                throw new Exception("Can not find version name from the given URL: $url");
            }

            $result = array(
                'version' => "php-{$matches[2]}",
                'url' => $url,
            );
        }

        return $result;
    }
}
