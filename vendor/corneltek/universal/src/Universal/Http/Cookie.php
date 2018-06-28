<?php
namespace Universal\Http;
use Exception;

/**
 *    $cookie = new Cookie;
 *    $cookie->path = '/path';
 *    $cookie->set( 'name' , 123 );
 */
class Cookie
{

    public $expire = 0;

    public $path;

    public $domain;

    public $secure = false;

    public $httponly = false;


    /**
     * setcookie API
     *
     *  bool setcookie ( 
     *      string $name [, string $value 
     *                   [, int $expire = 0 
     *                   [, string $path 
     *                   [, string $domain 
     *                   [, bool $secure = false 
     *                   [, bool $httponly = false ]]]]]] )
     */
    public function __construct($options = array() ) {
        foreach( array('expire','path','domain','secure','httponly') as $k ) {
            if( isset($options[$k]) )
                $this->$k = $options[$k];
        }
    }


    /**
     * Set cookie path
     *
     * @param string $path
     */
    public function setPath($path) { $this->path = $path; }

    public function setSecure($secure) { $this->secure = $secure; }



    public function calculateExpireTime($expire) {
        if( is_string($expire) ) {
            // parse from pretty date string
            if( preg_match('#(\d+)\s+(days?|hours?|minutes?|seconds?)#',$expire,$regs) ) {
                $num = (int) $regs[1];
                $length = $regs[2];
                $seconds = 1;
                switch($length) {
                    case 'day':
                    case 'days':
                        $seconds = 3600 * 24;
                        break;
                    case 'hour':
                    case 'hours':
                        $seconds = 3600;
                        break;
                    case 'minute':
                    case 'minutes':
                        $seconds = 60;
                        break;
                    case 'second':
                    case 'seconds':
                    default:
                        $seconds = 1;
                        break;
                }
                return time() + ($seconds * $num);
            } else {
                throw new \Exception('Unknown expire format');
            }
        }
        elseif( $expire < 0 || $expire > 1000000000 ) {
            // expired at (expire time)
            return $expire;
        }
        // incremental expire time (current time + seconds)
        return time() + $expire;
    }

    /**
     * Set expire time
     *
     * Acceptable formats:
     *  
     * - Duration string:
     *    1 hour
     *    3 days
     *    3 minutes
     *
     * - Unix Timestamp
     *    1344085885
     *
     * - Seconds
     *    10
     *    20
     *    30
     *
     * @param string|integer $expire
     *
     */
    public function setExpire($expire) { 
        $this->expire = $this->calculateExpireTime($expire);
    }

    public function setDomain($domain) { 
        $this->domain = $domain; 
    }

    public function remove($name) 
    {
        setcookie($name, NULL, -1);
    }

    public function set($name,$value, $expire = null , $path = null)
    {
        // build setcookie arguments
        $args = array($name,$value);
        if( $expire !== null || $this->expire ) {
            $args[] = $expire ? $this->calculateExpireTime($expire) : $this->expire;
            if( $path || $this->path ) {
                $args[] = $path ? $path : $this->path;
                if( $this->domain ) {
                    $args[] = $this->domain;
                    $args[] = $this->secure;
                    $args[] = $this->httponly;
                }
            }
        }
        // setcookie( $name, $value , $this->expire , $this->path , $this->domain, $this->secure, $this->httponly );
        return @call_user_func_array('setcookie',$args);
    }

    public function __set($name,$value) 
    {
        return $this->set($name,$value);
    }

    public function __get($name) {
        return $this->get($name);
    }

    public function get($name) {
        if( isset($_COOKIE[$name]) ) {
            return $_COOKIE[$name];
        }
    }
}

