<?php

namespace PhpBrew;

class Builder
{
    public $logger;

    public function __construct($version)
    {

    }

    public function prepare()
    {
        $home = Config::getPhpbrewRoot();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getVersionBuildPrefix( $version );

        if( ! file_exists($buildDir) )
            mkdir( $buildDir, 0755, true );

        if( ! file_exists($buildPrefix) )
            mkdir( $buildPrefix, 0755, true );


    }


    public function configure()
    {




    }

    public function build()
    {

    }

    public function test()
    {

    }

    public function install()
    {

    }

}


