<?php

namespace PhpBrew;

use CLIFramework\Logger;

class VariantParser
{
    public static function splitVariantValue($str)
    {
        if (strpos($str, '=') !== false) {
            list($name, $val) = explode('=', $str);

            return array($name => $val);
        }

        return array($str => true);
    }

    /**
     * @param string[] $args
     *
     * @return array
     *
     * @throws InvalidVariantSyntaxException
     */
    public static function parseCommandArguments(array $args, Logger $logger)
    {
        $extra = array();
        $enabledVariants = array();
        $disabledVariants = array();

        // split variant strings
        $startExtra = false;
        foreach ($args as $arg) {
            if ($arg === '--') {
                $startExtra = true;
                continue;
            }

            if ($startExtra) {
                $extra[] = $arg;
                continue;
            }

            if ($arg[0] === '+' || $arg[0] === '-') {
                if (substr($arg, 0, 2) === '--') {
                    throw new InvalidVariantSyntaxException(
                        "Invalid variant syntax exception start with '--': " . $arg
                    );
                }
                preg_match_all('#[+-][\w_]+(=[\"\'\.\/\w_-]+)?#', $arg, $variantStrings);

                if (isset($variantStrings[0])) {
                    $variantStrings = array_filter($variantStrings[0]);

                    if (count($variantStrings) > 1) {
                        $logger->warn('The usage of multiple variants in one command line argument is deprecated.');
                        $logger->warn('Please provide them as individual arguments: ' . implode(' ', $variantStrings));
                    }

                    foreach ($variantStrings as $str) {
                        if ($str[0] == '+') {
                            $a = self::splitVariantValue(substr($str, 1));
                            $enabledVariants = array_merge($enabledVariants, $a);
                        } elseif ($str[0] == '-') {
                            $a = self::splitVariantValue(substr($str, 1));
                            $disabledVariants = array_merge($disabledVariants, $a);
                        } else {
                            throw new InvalidVariantSyntaxException($str . ' is invalid syntax');
                        }
                    }
                }
            } else {
                throw new InvalidVariantSyntaxException("Unsupported variant syntax: $arg");
            }
        }

        return array(
            'enabled_variants' => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options' => $extra,
        );
    }

    /**
     * Reveal the variants info to command arguments.
     */
    public static function revealCommandArguments(array $info)
    {
        $args = array();

        foreach ($info['enabled_variants'] as $k => $v) {
            $arg = '+' . $k;

            if (!is_bool($v)) {
                $arg .= '=' . $v;
            }

            $args[] = $arg;
        }

        if (!empty($info['disabled_variants'])) {
            foreach ($info['disabled_variants'] as $k => $_) {
                $args[] = '-' . $k;
            }
        }

        if (!empty($info['extra_options'])) {
            $args = array_merge($args, array('--'), $info['extra_options']);
        }

        return implode(' ', $args);
    }
}
