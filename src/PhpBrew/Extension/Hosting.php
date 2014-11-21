<?php
/**
 * Created by PhpStorm.
 * User: rack
 * Date: 14/11/22
 * Time: 01:30
 */

namespace PhpBrew\Extension;


interface Hosting {

    public function getName();
    public function getOwner();
    public function setOwner($owner);
    public function getRepository();
    public function setRepository($repository);
    public function getPackageName();
    public function setPackageName($packageName);
    public function getExtensionListPath();
    public function getRemoteExtensionListUrl($branch);
    public function buildKnownReleasesUrl();
    public function parseKnownReleasesResponse($content);
    public function buildPackageDownloadUrl($version);
    public function exists($url, $packageName);

    public function getDefaultVersion();
    public function shouldLookupRecursive();
    public function resolveDownloadFileName($version);

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath);
    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath);
} 