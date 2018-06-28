<?php 
namespace Universal\Http;
use ArrayAccess;
use Universal\Http\FilesParameter;

/**
 * $req = new HttpRequest;
 * $v = $req->get->varname;
 * $b = $req->post->varname;
 *
 * $username = $req->param('username');
 *
 * $req->files->uploaded->name;
 * $req->files->uploaded->size;
 * $req->files->uploaded->tmp_name;
 * $req->files->uploaded->error;
 */
class HttpRequest
    implements ArrayAccess
{
    protected $_requestBodyFp;

    protected $_parameterBags = [];


    static $httpHeaderMapping = array(
        'HTTP_ACCEPT'                    => 'Accept',
        'HTTP_ACCEPT_CHARSET'            => 'Accept-Charset',
        'HTTP_ACCEPT_ENCODING'           => 'Accept-Encoding',
        'HTTP_ACCEPT_LANGUAGE'           => 'Accept-Language',
        'HTTP_CONNECTION'                => 'Connection',
        'HTTP_CACHE_CONTROL'             => 'Cache-Control',
        'HTTP_UPGRADE_INSECURE_REQUESTS' => 'Upgrade-Insecure-Requests',
        'HTTP_HOST'                      => 'Host',
        'HTTP_REFERER'                   => 'Referer',
        'HTTP_USER_AGENT'                => 'User-Agent',
    );

    /**
     * @var array parameters from $_FILES
     */
    public $files = array();


    /**
     * @var array parameters from $_REQUEST
     */
    public $parameters = array();


    /**
     * @var array parameters parsed from POST request method
     */
    public $bodyParameters = array();

    /**
     * @var array parameters parsed from query string
     */
    public $queryParameters = array();


    public $cookieParameters = array();


    /**
     * @var array parameters created from $_SERVER
     */
    public $serverParameters = array();

    public $sessionParameters = array();


    /**
     * When $parameters is defined, HttpRequest uses $parameters instead of the default $_REQUEST
     * When $files is ignored, HttpRequest uses $_FILES as the default file array.
     *
     * @param array|null $parameters The array of request parameter, usually $_REQUEST
     * @param array|null $files The array of files, usually $_FILES
     */
    public function __construct(array $parameters = null, array $files = null)
    {
        if ($parameters) {
            $this->parameters = $parameters;
        } else if (isset($_REQUEST)) {
            $this->parameters = $_REQUEST;
        }
        if ($files) {
            $this->files = FilesParameter::fix_files_array($files);
        } else if (isset($_FILES)) {
            $this->files = FilesParameter::fix_files_array($_FILES);
        }
    }


    public function openRequestBodyStream()
    {
        $input = 'php://input';
        if (isset($this->serverParameters['phpsgi.input'])) {
            $input = $this->serverParameters['phpsgi.input'];
        }
        return $this->_requestBodyFp = fopen($input);
    }

    public function closeRequestBodyStream()
    {
        if ($this->_requestBodyFp) {
            fclose($this->_requestBodyFp);
        }
    }

    public function getRequestBody()
    {
        $input = 'php://input';
        if (isset($this->serverParameters['phpsgi.input'])) {
            $input = $this->serverParameters['phpsgi.input'];
        }
        return file_get_contents($input);
    }

    /**
     * If request method is defined in $_SERVER, we return the request method
     * respectively, otherwise we return 'GET' by default.
     *
     * @return string the request method string.
     */
    public function getRequestMethod()
    {
        if (isset($this->serverParameters['REQUEST_METHOD'])) {
            return $this->serverParameters['REQUEST_METHOD'];
        }
        return 'GET';
    }

    /**
     * Check if we have the parameter
     *
     * @param string $name parameter name
     * @return boolean
     */
    public function hasParam($name)
    {
        return isset($this->parameters[$name]);
    }

    public function existsParam($name)
    {
        return array_key_exists($name, $this->parameters);
    }


    /**
     * @param string $field parameter field name
     */
    public function param($field)
    {
        if (isset($this->parameters[$field])) {
            return $this->parameters[$field];
        }
    }

    public function getFiles() 
    {
        return $this->files;
    }

    public function file($field)
    {
        if (isset($this->files[$field])) {
            return $this->files[$field];
        }
    }


    /**
     * Get request body if any
     *
     * @return string
     */
    public function getInput()
    {
        return file_get_contents('php://input');
    }


    /**
     * Parse submited body content return parameters
     *
     * @return array parameters
     */
    public function getInputParams()
    {
        $params = array();
        parse_str($this->getInput(), $params);
        return $params;
    }


    public function offsetSet($name,$value)
    {
        $this->parameters[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->parameters[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->parameters[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->paramemters[$name]);
    }

    public function getQueryParameters()
    {
        return $this->queryParameters;
    }

    public function getBodyParameters()
    {
        return $this->bodyParameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }


    public function __get($key)
    {
        if (isset($_parameterBags[$key])) {
            return $_parameterBags[$key];
        }

        // create parameter bag object and save it in cache
        switch($key)
        {
            case 'files':
                return $this->_parameterBags[$key]  = new FilesParameter($this->files);
            case 'post':
                return $this->_parameterBags[$key]  = new Parameter($this->bodyParameters);
            case 'get':
                return $this->_parameterBags[$key]  = new Parameter($this->queryParameters);
            case 'session':
                return $this->_parameterBags[$key]  = new Parameter($this->sessionParameters);
            case 'server':
                return $this->_parameterBags[$key]  = new Parameter($this->serverParameters);
            case 'request':
                return $this->_parameterBags[$key] = new Parameter($this->parameters);
            case 'cookie':
                return $this->_parameterBags[$key] = new Parameter($this->cookieParameters);
        }
    }



    /**
     * Converts global $_SERVER variables to header values.
     *
     * @return array
     */
    public static function createHeadersFromServerGlobal(array $server)
    {
        $headers = array();
        foreach (self::$httpHeaderMapping as $serverKey => $headerKey) {
            if (isset($server[$serverKey])) {
                $headers[$headerKey] = $server[$serverKey];
            }
        }
        // For extra http header fields
        foreach ($server as $key => $value) {
            if (isset(self::$httpHeaderMapping[$key])) {
                continue;
            }
            if ('HTTP_' === substr($key,0,5)) {
                $headerField = join('-',array_map('ucfirst',explode('_', strtolower(substr($key,5)))));
                $headers[$headerField] = $value;
            }
        }
        return $headers;
    }




    public function __destruct()
    {
        // If the request body stream is opened,
        // We should close it when this request object not needed anymore.
        if ($this->_requestBodyFp) {
            $this->closeRequestBodyStream();
        }
    }


    /**
     * Create request object from superglobal $GLOBALS
     *
     * @param $globals The $GLOBALS
     * @return HttpRequest
     */
    static public function createFromGlobals(array $globals)
    {
        $request = new self;
        if (isset($globals['_POST'])) {
            $request->bodyParameters = $globals['_POST'];
        }
        if (isset($globals['_GET'])) {
            $request->queryParameters = $globals['_GET'];
        }
        if (isset($globals['_REQUEST'])) {
            $request->parameters = $globals['_REQUEST'];
        }
        if (isset($globals['_COOKIE'])) {
            $request->cookieParameters = $globals['_COOKIE'];
        }
        if (isset($globals['_SESSION'])) {
            $request->sessionParameters = $globals['_SESSION'];
        }
        if (isset($globals['_FILES'])) {
            $request->files = FilesParameter::fix_files_array($globals['_FILES']);
        }
        if (isset($globals['_SERVER'])) {
            $request->serverParameters = $globals['_SERVER'];
        }
        return $request;
    }







}

