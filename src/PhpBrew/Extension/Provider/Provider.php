<?php

namespace PhpBrew\Extension\Provider;


interface Provider {

    public static function getName();
    public function getOwner();
    public function setOwner($owner);
    public function getRepository();
    public function setRepository($repository);
    public function getPackageName();
    public function setPackageName($packageName);
    public function buildKnownReleasesUrl();
    public function parseKnownReleasesResponse($content);
    public function buildPackageDownloadUrl($version);
    public function exists($url, $packageName);
    public function isBundled($packageName);

    public function getDefaultVersion();
    public function setDefaultVersion($version);
    public function shouldLookupRecursive();
    public function resolveDownloadFileName($version);

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath);
    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath);
}
