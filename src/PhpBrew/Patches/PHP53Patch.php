<?php
namespace PhpBrew\Patches;
use PhpBrew\Buildable;
use PhpBrew\PatchKit\Patch;
use PhpBrew\PatchKit\RegExpPatchRule;
use PhpBrew\PatchKit\DiffPatchRule;
use CLIFramework\Logger;


class GistContent
{
    public $userId;

    public $gistId;

    public $revision;

    public $filename;

    public function __construct($userId, $gistId, $filename = null, $revision = null)
    {
        $this->userId = $userId;
        $this->gistId = $gistId;
        $this->filename = $filename;
        $this->revision = $revision;
    }

    static public function url($userId, $gistId, $filename = null, $revision = null)
    {
        $gist = new self($userId, $gistId, $filename, $revision);
        return $gist->__toString();
    }

    public function __toString()
    {
        $url = "https://gist.githubusercontent.com/{$this->userId}/{$this->gistId}/raw";
        if ($this->revision) {
            $url .= "/{$this->revision}";
        }
        if ($this->filename) {
            $url .= "/{$this->filename}";
        }
        return $url;
    }
}

class PHP53Patch extends Patch
{
    public function desc()
    {
        return "php5.3.29 multi-sapi patch."; // use generic patch description when there are more than one 5.3 patches
    }

    public function match(Buildable $build, Logger $logger)
    {
        return $build->osName === "Darwin" && version_compare($build->getVersion(), '5.3.29') === 0;
    }


    public function rules()
    {
        $rules = [];
        // The patch only works for php5.3.29
        $rules[] = DiffPatchRule::from(GistContent::url('javian', 'bfcbd5bc5874ee9c539fb3d642cdce3e', 'multi-sapi-5.3.29-homebrew.patch', 'bf079cc68ec76290f02f57981ae85b20a06dd428'))
            ->strip(1);
        return $rules;
    }



}





