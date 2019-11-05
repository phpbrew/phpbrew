<?php

namespace PhpBrew\Patches;

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

    public static function url($userId, $gistId, $filename = null, $revision = null)
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
