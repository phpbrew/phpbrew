<?php
namespace SerializerKit;
use Exception;
use Symfony\Component\Yaml\Yaml as sfYaml;

class YamlSerializer
{
    const yaml   = 1;
    const syck   = 2;
    const sfyaml = 3;
    const spyc   = 4;

    public $backend;

    function __construct($backend = null)
    {
        if( null !== $backend ) {
            $this->backend = $backend;
            return;
        }

        if( extension_loaded('yaml') ) {
            $this->backend = self::yaml;
        } elseif( extension_loaded('syck') ) {
            $this->backend = self::syck;
        } else {
            $this->backend = self::sfyaml;
        }
    }

    function encode($data) 
    {
        switch($this->backend) {
            case self::yaml:
                return yaml_emit($data, YAML_UTF8_ENCODING ); 
                break;
            case self::syck:
                return syck_dump( $data );
                break;
            case self::sfyaml:
                return sfYaml::dump($data,2);
                break;
        }
    }

    function decode($data) 
    {
        switch($this->backend) {
            case self::yaml:
                return yaml_parse($data); 
                break;
            case self::syck:
                return syck_load($data);
                break;
            case self::sfyaml:
                return sfYaml::parse($data);
                break;
        }
        throw new Exception('can not decode yaml: extension or library is required.');
    }
}



