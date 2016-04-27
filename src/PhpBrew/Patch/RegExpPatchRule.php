<?php
namespace PhpBrew\Patch;

/**
 * Defines a rule of a replacement using regexp.
 */
class RegExpPatchRule
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $replacement;

    /**
     * @var Callable
     */
    private $isReplacementRequired;

    private function __construct($pattern, $replacement, $isReplacementRequired)
    {
        $this->pattern = $pattern;
        $this->replacement = $replacement;
        $this->isReplacementRequired = $isReplacementRequired;
    }

    /**
     * Replaces always.
     */
    public static function always($pattern, $replacement)
    {
        return new RegexpPatchRule($pattern, $replacement, function() {
            return true;
        });
    }

    /**
     * Replaces if one of preconditions is satisfied.
     */
    public static function anyOf($conditions, $pattern, $replacement)
    {
        return new RegexpPatchRule($pattern, $replacement, function($line) use ($conditions) {
            foreach ($conditions as $condition) {
                if (preg_match($condition, $line)) {
                    return true;
                }
            }

            return count($conditions) === 0 ? true : false;
        });
    }

    /**
     * Replaces if all of preconditions are satisfied.
     */
    public static function allOf($conditions, $pattern, $replacement)
    {
        return new RegexpPatchRule($pattern, $replacement, function($line) use ($conditions) {
            foreach ($conditions as $condition) {
                if (!preg_match($condition, $line)) {
                    return false;
                }
            }
            return true;
        });
    }

    public function apply($subject)
    {
        $lines = preg_split("/(?:\r\n|\n|\r)/", $subject);
        $size = count($lines);
        for ($i = 0; $i < $size; ++$i) {
            if (call_user_func($this->isReplacementRequired, $lines[$i])) {
                $lines[$i] = preg_replace($this->pattern, $this->replacement, $lines[$i]);
            }
        }
        return implode($lines, PHP_EOL);
    }
}
