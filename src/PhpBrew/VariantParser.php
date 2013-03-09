<?php
namespace PhpBrew;
use Exception;
use PhpBrew\Exceptions\OopsException;

class VariantParser
{

    static function splitVariantString($str)
    {
        if( strpos($str,'=') !== false ) {
            list($name,$val) = explode('=',$str);
            return array( $name => $val );
        }
        return array( $str => true );
    }


    static function parseCommandArguments($args)
    {
        $extra = array();

        $enabledVariants = array();
        $disabledVariants = array();

        // split variant strings
        $startExtra = false;
        foreach( $args as $arg ) {
            if( $arg === '--' ) {
                $startExtra = true;
                continue;
            }

            if( ! $startExtra ) {
                if( $arg[0] === '+' || $arg[0] === '-' ) {
                    if( substr($arg,0,2) === '--' ) {
                        throw new Exception("Invalid variant option $arg");
                    }

                    $variantStrs = preg_split('#(?=[+-])#', $arg);
                    $variantStrs = array_filter($variantStrs);

                    foreach( $variantStrs as $str ) {
                        if($str[0] == '+') {
                            $a = self::splitVariantString( substr($str,1) );
                            $enabledVariants = array_merge( $enabledVariants, $a );
                        } elseif($str[0] == '-' ) {
                            $a = self::splitVariantString( substr($str,1) );
                            $disabledVariants = array_merge( $disabledVariants, $a );
                        } else {
                            throw new OopsException;
                        }
                    }


                } else {
                    throw new Exception("Invalid variant option $arg");
                }
            } else {
                $extra[] = $arg;
            }
        }
        return array(
            'enabled_variants' => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options' => $extra,
        );

    }


    static function revealCommandArguments($info)
    {
        $out = '';

        foreach( $info['enabled_variants'] as $k => $v ) {
            $out .= '+' . $k;
            if (! is_bool($v)) {
                $out .= '=' . $v . ' ';
            }
        }
        if( ! empty($info['disabled_variants']) ) {
            $out .= " " . '-' . join('-', array_keys($info['disabled_variants']));
        }

        if( ! empty($info['extra_options']) ) {
            $out .= " " . '-- ' . join(' ', $info['extra_options']);
        }
        return $out;
    }

}



