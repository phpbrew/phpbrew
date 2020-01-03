<?php

namespace PhpBrew;

class VariantParser
{
    /**
     * @param string[] $args
     *
     * @return array
     *
     * @throws InvalidVariantSyntaxException
     */
    public static function parseCommandArguments(array $args)
    {
        $extra = array();
        $enabledVariants = array();
        $disabledVariants = array();

        while (true) {
            $arg = array_shift($args);

            if ($arg === null) {
                break;
            }

            if ($arg === '') {
                throw new InvalidVariantSyntaxException('Variant cannot be empty');
            }

            if ($arg === '--') {
                $extra = $args;
                break;
            }

            $operator = substr($arg, 0, 1);

            switch ($operator) {
                case '+':
                    $target =& $enabledVariants;
                    break;
                case '-':
                    $target =& $disabledVariants;
                    break;
                default:
                    throw new InvalidVariantSyntaxException('Variant must start with a + or -');
            }

            $variant            = substr($arg, 1);
            list($name, $value) = array_pad(explode('=', $variant, 2), 2, null);

            if ($name === '') {
                throw new InvalidVariantSyntaxException('Variant name cannot be empty');
            }

            $target[$name] = $value;
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
