<?php
namespace Universal\Http;

class HttpResponse
{

    /**
     * @var integer
     */
    public $code;


    /**
     * @var string
     */
    public $status; /* status message for Code */


    /**
     * @var string
     */
    public $contentType;


    /**
     * @var string 
     */
    public $cacheControl;

    /**
     * @var integer time
     *
     * Cache-Expires 
     */
    public $expires;


    /**
     * @var string
     */
    public $body;



    /**
     * @param integer $code status code
     * @param string $status status message
     */
    public function __construct($code = 200, $status = 'OK') 
    {
        $this->code = $code;
        $this->status = $status;
    }



    /**
     * Set status code
     *
     * @param string $code set status code.
     */
    public function code($code)
    {
        $this->code = $code;
    }



    /**
     * Set status message
     *
     * @param string $status Status message.
     */
    public function status($status)
    {
        $this->status = $status;
    }


    /**
     * Send location to header
     *
     * @param string $url
     *
     */
    public function location($url)
    {
        header( 'Location: ' . $url );
    }

    /**
     * Redirect to URL (Temporarily)
     *
     * @param string $url
     */
    public function redirect($url) 
    {
        $this->code(302);
        $this->location($url);
    }


    /**
     * Redirect permanently
     *
     * @param string $url
     */
    public function redirectPermanently($url)
    {
        $this->code(301);
        $this->status('Moved Permanently');
        $this->location($url);
    }

    /**
     * Redirect to URL (delayed)
     *  
     * @param string $url
     * @param integer $seconds (default = 1)
     */
    public function redirectLater($url, $seconds = 1) 
    {
        header( "refresh: $seconds; url=$url" );
    }


    /**
     * set content type
     *
     * @param string $contentType eg. text/html
     *
     * @code
     *
     *     $response->contentType('text/html');
     *
     * @endcode
     */
    public function contentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function body($body)
    {
        $this->body = $body;
    }


    /**
     * Set cache-control to header
     *
     * @param string $desc cache control string
     */
    public function cacheControl($desc) 
    {
        $this->cacheControl = $desc;
    }


    /**
     * Set cache-control to no-cahche
     */
    public function noCache() 
    {
        $this->cacheControl = 'no-cache, must-revalidate';
    }

    /**
     * Set cache expiry time
     *
     * @param integer $seconds
     */
    public function cacheExpiryTime($seconds) 
    {
        $this->expires = time() + $seconds;
    }


    /**
     * HTTP Status Code Helper Methods
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * @link http://restpatterns.org/HTTP_Status_Codes
     *
     * REST Pattern
     * @link http://restpatterns.org/
     */
    public function codeOk() 
    {
        $this->code = 200;
        $this->status = 'OK';
    }

    public function codeCreated()
    {
        $this->code = 201;
        $this->status = 'Created';
    }

    public function codeAccepted()
    {
        $this->code = 202;
        $this->status = 'Accepted';
    }

    public function codeNoContent() 
    {
        $this->code = 204;
        $this->status = 'No Content';
    }

    public function codeBadRequest()
    {
        $this->code = 400;
        $this->status = 'Bad Request';
    }

    public function codeForbidden()
    {
        $this->code = 403;
        $this->status = 'Forbidden';
    }

    public function codeNotFound()
    {
        $this->code = 404;
        $this->status = 'Not found';
    }


    public function finalize()
    {
        if( $this->code ) {
            @header('HTTP/1.1 ' . $this->code . ' ' . $this->status );
        }
        if( $this->contentType ) {
            @header("Content-type: " . $this->contentType );
        }
        if( $this->cacheControl ) {
            @header('Cache-Control: '  . $this->cacheControl); // HTTP/1.1
        }
        if( $this->expires ) {
            $datestr = gmdate(DATE_RFC822, $this->expires );
            // header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
            @header( "Expires: $datestr" );
        }
        return $this->body;
    }

    public function __toString() 
    {
        return $this->finalize();
    }

}



