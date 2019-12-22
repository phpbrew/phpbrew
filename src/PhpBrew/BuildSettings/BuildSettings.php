<?php

namespace PhpBrew\BuildSettings;

use Exception;

class BuildSettings
{
    /**
     * @var array<string,string|null>
     */
    private $enabledVariants = array();

    /**
     * @var array<string,null>
     */
    private $disabledVariants = array();

    /**
     * @var array<string>
     */
    private $extraOptions = array();

    public function __construct(array $settings = array())
    {
        if (isset($settings['enabled_variants'])) {
            $this->enableVariants($settings['enabled_variants']);
        }
        if (isset($settings['disabled_variants'])) {
            $this->disableVariants($settings['disabled_variants']);
        }
        if (isset($settings['extra_options'])) {
            $this->extraOptions = array_merge($this->extraOptions, $settings['extra_options']);
        }
    }

    public function toArray()
    {
        return array(
            'enabled_variants' => $this->enabledVariants,
            'disabled_variants' => $this->disabledVariants,
            'extra_options' => $this->extraOptions,
        );
    }

    public function enableVariants(array $settings)
    {
        foreach ($settings as $name => $value) {
            $this->enableVariant($name, $value);
        }
    }

    public function enableVariant($name, $value = null)
    {
        $this->enabledVariants[$name] = $value;
    }

    public function disableVariants(array $settings)
    {
        foreach ($settings as $name => $value) {
            $this->disableVariant($name);
        }
    }

    /**
     * Disable variant.
     *
     * @param string $name The variant name.
     */
    public function disableVariant($name)
    {
        $this->disabledVariants[$name] = null;
    }

    /**
     * Remove the enabled the variants since we've disabled
     * them.
     */
    public function resolveVariants()
    {
        foreach ($this->disabledVariants as $name => $_) {
            $this->removeVariant($name);
        }
    }

    public function isEnabledVariant($name)
    {
        return array_key_exists($name, $this->enabledVariants);
    }

    /**
     * Remove enabled variant.
     */
    public function removeVariant($name)
    {
        unset($this->enabledVariants[$name]);
    }

    /**
     * Get enabled variants.
     */
    public function getEnabledVariants()
    {
        return $this->enabledVariants;
    }

    /**
     * Get all disabled variants.
     */
    public function getDisabledVariants()
    {
        return $this->disabledVariants;
    }

    public function getExtraOptions()
    {
        return $this->extraOptions;
    }

    /**
     * Load and return the variant info from file.
     */
    public function loadVariantInfoFile($variantFile)
    {
        if (!is_readable($variantFile)) {
            throw new Exception(
                "Can't load variant info! Variants file {$variantFile} is not readable."
            );
        }
        $variantInfo = unserialize(file_get_contents($variantFile));

        $this->loadVariantInfo($variantInfo);
    }

    public function writeVariantInfoFile($variantInfoFile)
    {
        return file_put_contents($variantInfoFile, serialize(array(
            'enabled_variants' => $this->enabledVariants,
            'disabled_variants' => $this->disabledVariants,
            'extra_options' => array_unique($this->extraOptions),
        )));
    }

    public function loadVariantInfo(array $variantInfo)
    {
        if (isset($variantInfo['enabled_variants'])) {
            foreach ($variantInfo['enabled_variants'] as $variant => $value) {
                if ($value === true) {
                    // TRUE no longer indicates the absence of a prefix, NULL does
                    $this->enableVariant($variant);
                } else {
                    $this->enableVariant($variant, $value);
                }
            }
        }

        if (isset($variantInfo['disabled_variants'])) {
            $this->disableVariants($variantInfo['disabled_variants']);
        }

        if (isset($variantInfo['extra_options'])) {
            $this->extraOptions = array_unique(array_merge($this->extraOptions, $variantInfo['extra_options']));
        }

        $this->resolveVariants();
    }
}
