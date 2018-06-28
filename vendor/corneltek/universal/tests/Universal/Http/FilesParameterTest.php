<?php 
use Universal\Http\HttpRequest;

function create_file_hash() {
    if( ! extension_loaded('Fileinfo') ) {
        throw new Exception('Fileinfo extension is required.');
    }
    $files = func_get_args();
    $name     = array();
    $tmp_name = array();
    $size     = array();
    $type     = array();
    $error    = array();
    foreach( (array) $files as $file ) {
        $finfo = new finfo(FILEINFO_MIME);
        $ftype  = $finfo->file($file);
        $mime  = substr($ftype, 0, strpos($ftype, ';'));

        $name[]     = basename($file);
        $tmp_name[] = realpath($file);
        $size[]     = filesize($file);
        $type[]     = $mime;
        $error[]    = 0;
    }
    return array(
        'name'     => count($name) == 1 ? $name[0] : $name,
        'tmp_name' => count($tmp_name) == 1 ? $tmp_name[0] : $tmp_name,
        'size'     => count($size) == 1 ? $size[0] : $size,
        'type'     => count($type) == 1 ? $type[0] : $type,
        'error'    => count($error) == 1 ? $error[0] : $error,
    );
}

class HttpFilesParameterTest extends PHPUnit_Framework_TestCase
{
    public function testFunc()
    {
        $files = array( );
        $files['uploaded'] = create_file_hash('tests/data/cat.txt');

        $req = new HttpRequest([], $files);
        ok(isset($req->files['uploaded']), 'Got uploaded file field' );

        is(11, $req->files['uploaded']['size'] );
        is( 'text/plain', $req->files['uploaded']['type'] );
        is( 0, $req->files['uploaded']['error'] );

        ok(isset($req->files['uploaded']));
        $file = $req->files['uploaded'];
    }

    function testFunc2()
    {
        $files = array( );
        $files['uploaded'] = create_file_hash(
            'tests/data/cat.txt',
            'tests/data/cat2.txt'
        );

        $req = new HttpRequest([], $files);
        ok( is_array( $req->files['uploaded'] ) );
        foreach( $req->files['uploaded'] as $f ) {
            ok($f);
        }
    }
}

