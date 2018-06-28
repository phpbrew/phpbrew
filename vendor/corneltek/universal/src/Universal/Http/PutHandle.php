<?php
namespace Universal\Http;

/**
 * $put = new Universal\Http\Put;
 * $data = $put->read(1024);
 * $put->close();
 *
 * TODO:
 *
 * use SplFileObject
 */
class PutHandle
{
    private $handle;

    function __construct()
    {
        $this->handle = fopen('php://input','r');
    }

    function read($i = 1024)
    {
        return fread($this->handle, $i);
    }

    function close()
    {
        fclose($this->handle);
    }

}
