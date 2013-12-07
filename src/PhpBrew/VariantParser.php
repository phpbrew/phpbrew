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

        // inherit variants
        if (!empty($enabledVariants['inherit'])) {
            $inheritedVariants = self::getInheritedVariants($enabledVariants['inherit']);

            if (isset($inheritedVariants['enabled_variants'])
                && is_array($inheritedVariants['enabled_variants'])
            ) {
            	$enabledVariants = array_merge(
            	    $inheritedVariants['enabled_variants'],
            	    $enabledVariants
                );
            }

            if (isset($inheritedVariants['disabled_variants'])
                && is_array($inheritedVariants['disabled_variants'])
            ) {
                $disabledVariants = array_merge(
                    $inheritedVariants['disabled_variants'],
                    $disabledVariants
                );
            }

            if (isset($inheritedVariants['extra_options'])
                && is_array($inheritedVariants['extra_options'])
            ) {
                $extra = array_merge(
                    $inheritedVariants['extra_options'],
                    $extra
                );
            }

            unset($enabledVariants['inherit']);

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

    /**
     * Returns array with the variants for the
     * given version
     * @param string $version
     * @throws Exception
     * @return mixed
     */
    public static function getInheritedVariants($version)
    {
        if (!preg_match('/^php-/', $version)) {
            $version = 'php-' . $version;
        }

        $installedVersions = Config::getInstalledPhpVersions();

        if (array_search($version, $installedVersions) === false) {
        	throw new Exception(
    	       "Can't inherit variants from {$version} because this version is not installed!"
            );
        }
        $variantsFile = Config::getVersionBuildPrefix($version)
                      . DIRECTORY_SEPARATOR . 'phpbrew.variants';

        if (!is_readable($variantsFile)) {
        	throw new Exception(
        	    "Can't inherit variant from {$version}!"
        	    . "Variants file {$variantsFile} is not readable."
            );
        }

        return unserialize(file_get_contents($variantsFile));
    }

}



