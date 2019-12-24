<?php

namespace PhpBrew;

/**
 * An immutable object representing the parameters used to call `./configure'
 */
final class ConfigureParameters
{
    /**
     * Command line options and their values
     *
     * @var array<string,string|null>
     */
    private $options = array();

    /**
     * Paths passed to the command via the PKG_CONFIG_PATH environment variable
     *
     * @var array<string,null>
     */
    private $pkgConfigPaths = array();

    /**
     * Creates a new object with the given option and the value.
     *
     * If the given option is already set with the same value, the same object is returned.
     *
     * @param string      $option
     * @param string|null $value
     *
     * @return static
     */
    public function withOption($option, $value = null)
    {
        if (array_key_exists($option, $this->options) && $this->options[$option] === $value) {
            return $this;
        }

        $new = clone $this;
        $new->options[$option] = $value;

        return $new;
    }

    /**
     * Creates a new object with added value of PKG_CONFIG_PATH.
     *
     * If the given path is already added, the same object is returned.
     *
     * @param string $path
     *
     * @return static
     */
    public function withPkgConfigPath($path)
    {
        if (array_key_exists($path, $this->pkgConfigPaths)) {
            return $this;
        }

        $new = clone $this;
        $new->pkgConfigPaths[$path] = null;

        return $new;
    }

    /**
     * @return array<string,string|null>
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array<string>
     */
    public function getPkgConfigPaths()
    {
        return array_keys($this->pkgConfigPaths);
    }
}
