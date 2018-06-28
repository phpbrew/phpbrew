<?php
namespace Universal\Exception;
use RuntimeException;

class UploadException extends RuntimeException
{
    protected $file = array();

    public function __construct(array $filestash = array(), $message, $code = 0, $previous = null) {
        $this->file = $filestash;
        parent::__construct($message, $code, $previous);
    }

    /*
    public function __debugInfo() {
        return [
            'file' => $this->file,
            'message' => $this->message,
        ];
    }
    */




}




