<?php

namespace PhpBrew\Extension\Provider;

use PhpBrew\Config;

class GithubProvider implements Provider {

    public $site = 'github.com';
    public $owner = NULL;
    public $repository = NULL;
    public $packageName = NULL;
    public $defaultVersion = 'master';

    public static function getName() {
        return 'github';
    }

    public function buildPackageDownloadUrl($version='stable')
    {
        if (($this->getOwner() == NULL) || ($this->getRepository() == NULL)) {
            throw new Exception("Username or Repository invalid.");
        }
        return sprintf('https://%s/%s/%s/archive/%s.tar.gz', $this->site, $this->getOwner(), $this->getRepository(), $version);
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    public function getPackageName()
    {
        return $this->packageName;
    }

    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    public function exists($dsl, $packageName = NULL)
    {
        $dslparser = new RepositoryDslParser();
        $info = $dslparser->parse($dsl);

        $this->setOwner($info['owner']);
        $this->setRepository($info['package']);
        $this->setPackageName($packageName ?: $info['package']);

        return $info['repository'] == 'github';
    }

    public function isBundled($name)
    {
        return false;
    }

    public function buildKnownReleasesUrl()
    {
        return sprintf("https://api.github.com/repos/%s/%s/tags", $this->getOwner(), $this->getRepository());
    }

    public function parseKnownReleasesResponse($content)
    {
        $info = json_decode($content, TRUE);
        $versionList = array_map(function($version) {
            return $version['name'];
        }, $info);

        return $versionList;
    }

    public function getDefaultVersion()
    {
        return $this->defaultVersion;
    }

    public function setDefaultVersion($version)
    {
        $this->defaultVersion = $version;
    }

    public function shouldLookupRecursive()
    {
        return true;
    }

    public function resolveDownloadFileName($version)
    {
        return sprintf("%s-%s.tar.gz", $this->getRepository(), $version);
    }

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $cmds = array(
            "tar -C $currentPhpExtensionDirectory -xzf $targetFilePath"
        );
        return $cmds;
    }

    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $targetPkgDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getPackageName();
        $extractDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getRepository() . '-*';

        $cmds = array(
            "rm -rf $targetPkgDir",
            "mv $extractDir $targetPkgDir"
        );
        return $cmds;
    }

}
