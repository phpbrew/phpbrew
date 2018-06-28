<?php
namespace Universal\Http;

class StreamResponse
{
    public $boundary;


    /**
     * Currently implements MXHR interface
     */
    function __construct() {
        $this->boundary = md5(mt_rand() . time());

        // prevent error reporting
        set_error_handler(function() { return false; });

        header("Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"");

        if( function_exists('apache_setenv') ) {
            apache_setenv('no-gzip', 1);
        }
        ini_set('zlib.output_compression', 0);
        ini_set('implicit_flush', 1);
        set_time_limit(0);


        // close output buffers and flush them
        while( ob_get_level() ) { 
            ob_end_flush(); 
        }
        ob_implicit_flush(1);
        restore_error_handler();
    }


    /**
     * Write content to stream
     *
     *  @param string $content
     *  @param array $headers
     */
    public function write($content, $headers = array() ) {
        echo "--{$this->boundary}\n";

        if( is_array($headers) ) {
            foreach( $headers as $k => $v ) {
                echo $k . ':';
                if( is_array($v) ) {
                    echo join(';',$v);
                } else {
                    echo $v;
                }
                echo "\n\n";
            }
        } else {
            echo $headers;
            echo "\n";
        }
        echo $content;
        echo PHP_EOL;
        flush();
    }

    public function finalize()
    {
        echo "--{$this->boundary}--\n";
    }
}


