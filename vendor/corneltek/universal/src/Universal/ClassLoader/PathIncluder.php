<?php 
namespace Universal\ClassLoader;
if( ! class_exists('\Universal\ClassLoader\PathIncluder') ) {

/**
 * Include Path manipulator
 *
 * provides a simple api for include path
 *
 * $includer = new PathIncluder;
 * $includer->add( 'path/to/lib' );
 * $includer->setup();   // write set_include_path
 */
class PathIncluder 
{

    /**
     * @var array Custom include paths
     */
    private $paths;

    /**
     * @var array Original include paths
     */
    private $origPaths;

    function __construct($paths) 
    {
        $this->origPaths = explode(PATH_SEPARATOR,get_include_path());
        $this->paths = array_merge( (array) $paths , $this->origPaths );
    }

    /**
     * @return array include paths
     */
    function getPaths()
    {
        return $this->paths;
    }


    /**
     * remove include path
     *
     * @param string $path
     */
    function remove($path)
    {
        // search and remove
        if( ($index = array_search( $path , $this->paths )) !== FALSE ) {
            unset( $this->paths[ $index ] );
        }
    }

    /**
     * insert path at beginning
     *
     * @param string $path 
     */
    function insert($path)
    {
        array_unshift( $this->paths, $path );
    }


    /**
     * add include path
     *
     * @param string $path
     */
    function add($path)
    {
        $this->paths[] = $path;
    }

    /**
     * inflate path array with PATH_SEPARATOR
     */
    function inflate()
    {
        return join( PATH_SEPARATOR , $this->paths );
    }

    /**
     * set include path
     */
    function setup()
    {
        set_include_path( join( PATH_SEPARATOR , $this->paths ) );
    }

}

}
