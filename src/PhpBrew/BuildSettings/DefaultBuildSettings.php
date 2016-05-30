<?php

namespace PhpBrew\BuildSettings;

class DefaultBuildSettings extends BuildSettings
{
    public function __construct(array $settings = array())
    {
        $this->enableVariants(array(
            'bcmath' => true,
            'bz2' => true,
            'calendar' => true,
            'cli' => true,
            'ctype' => true,
            'dom' => true,
            'fileinfo' => true,
            'filter' => true,
            'ipc' => true,
            'json' => true,
            'mbregex' => true,
            'mbstring' => true,
            'mhash' => true,
            'pcntl' => true,
            'pcre' => true,
            'pdo' => true,
            'phar' => true,
            'posix' => true,
            'readline' => true,
            'sockets' => true,
            'tokenizer' => true,
            'xml' => true,
            'curl' => true,
            'zip' => true,
            'openssl' => 'yes',
        ));
        parent::__construct($settings);
    }
}
