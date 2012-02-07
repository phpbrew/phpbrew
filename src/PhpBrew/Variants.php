<?php

class Variants
{
    public function __construct()
    {
        $this->variants['/php-5.4/'] = array(
            'mysql' => array( 
                    '--with-mysql',
                    '--with-mysqli'
                ),
            'pdo' => array( '--enable-pdo' ),
            'cli' => array( '--enable-cli' ),
        );
    }
}

