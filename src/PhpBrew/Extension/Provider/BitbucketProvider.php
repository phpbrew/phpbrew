<?php

namespace PhpBrew\Extension\Provider;

class BitbucketProvider implements Provider
{
    public $site = 'bitbucket.org';
    public $owner = null;
    public $repository = null;
    public $packageName = null;
    public $defaultVersion = 'master';

    public static function getName()
    {
        return 'bitbucket';
    }

    public function buildPackageDownloadUrl($version = 'stable')
    {
        if (($this->getOwner() == null) || ($this->getRepository() == null)) {
            throw new \Exception('Username or Repository invalid.');
        }

        return sprintf('https://%s/%s/%s/get/%s.tar.gz', $this->site, $this->getOwner(), $this->getRepository(), $version);
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

    public function exists($dsl, $packageName = null)
    {
        $dslparser = new RepositoryDslParser();
        $info = $dslparser->parse($dsl);

        $this->setOwner($info['owner']);
        $this->setRepository($info['package']);
        $this->setPackageName($packageName ?: $info['package']);

        return $info['repository'] == 'bitbucket';
    }

    public function isBundled($name)
    {
        return false;
    }

    public function buildKnownReleasesUrl()
    {
        return sprintf('https://bitbucket.org/api/1.0/repositories/%s/%s/tags/', $this->getOwner(), $this->getRepository());
    }

    public function parseKnownReleasesResponse($content)
    {
        $info = json_decode($content, true);
        $versionList = array_keys($info);

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
        return sprintf('%s-%s-%s.tar.gz', $this->getOwner(), $this->getRepository(), $version);
    }

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $cmds = array(
            "tar -C $currentPhpExtensionDirectory -xzf $targetFilePath",
        );

        return $cmds;
    }

    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $targetPkgDir = $currentPhpExtensionDirectory.DIRECTORY_SEPARATOR.$this->getPackageName();
        $extractDir = $currentPhpExtensionDirectory.DIRECTORY_SEPARATOR.$this->getOwner().'-'.$this->getRepository().'-*';

        $cmds = array(
            "rm -rf $targetPkgDir",
            "mv $extractDir $targetPkgDir",
        );

        return $cmds;
    }
}
