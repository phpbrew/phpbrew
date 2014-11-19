<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\GithubExtensionList;
use PhpBrew\Tasks\FetchGithubExtensionListTask;
use RuntimeException;
use PhpBrew\Config;
use PhpBrew\Utils;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use GetOptionKit\OptionResult;

class KnownCommand extends \CLIFramework\Command
{

    public $extensionHosting = array();

    public function usage()
    {
        return 'phpbrew [-dv, -r] ext known extension_name';
    }

    public function brief()
    {
        return 'List known versions';
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
    }

    public function fetchRemoteExtensionInfo($options = NULL)
    {

        if ($this->extensionHosting['site'] == 'github') {
            $url = sprintf("https://api.github.com/repos/%s/%s/tags", $this->extensionHosting['owner'], $this->extensionHosting['repository']);
        } else {
            $url = sprintf("http://pecl.php.net/rest/r/%s/allreleases.xml", $this->extensionHosting['repository']);
        }

        $curlOptions = array(CURLOPT_USERAGENT => 'curl/'. curl_version()['version']);
        if (extension_loaded('curl')) {
            $downloader = new CurlDownloader;
            $downloader->setProgressHandler(new ProgressBar);

            if (! $options || ($options && ! $options->{'no-progress'}) ) {
                $downloader->setProgressHandler(new ProgressBar);
            }

            if ($options) {
                if ($proxy = $options->{'http-proxy'}) {
                    $downloader->setProxy($proxy);
                }
                if ($proxyAuth = $options->{'http-proxy-auth'}) {
                    $downloader->setProxyAuth($proxyAuth);
                }
            }
            $info = $downloader->request($url, array(), $curlOptions);
        } else {
            $info = file_get_contents($url);
        }
        return $info;

    }

    public function processeExtensionVersionList($info, $options = NULL)
    {

        $versionList = array();

        if ($this->extensionHosting['site'] == 'github') {
            $info2 = json_decode($info, TRUE);
            $versionList = array_map(function($version) {
                return $version['name'];
            }, $info2);
        } else {
            // convert xml to array
            $xml = simplexml_load_string($info);
            $json = json_encode($xml);
            $info2 = json_decode($json, TRUE);

            $versionList = array_map(function($version) {
                return $version['v'];
            }, $info2['r']);
        }

        return $versionList;
    }

    public function execute($extensionName)
    {

        $extensionList = new GithubExtensionList;

        // initial local list
        if (!$extensionList->foundLocalExtensionList() || $this->options->update) {
            $fetchTask = new FetchGithubExtensionListTask($this->logger, $this->options);
            $fetchTask->fetch('master');
        }

        $githubExtension = $extensionList->checkGithubExtension($extensionName);
        if ($githubExtension) {
            $this->extensionHosting = array(
                'site' => 'github',
                'owner' => $githubExtension['owner'],
                'repository' => $githubExtension['repository']
            );
        } else {
            $this->extensionHosting = array(
                'site' => 'pecl',
                'repository' => $extensionName
            );
        }

        $extensionInfo = $this->fetchRemoteExtensionInfo($this->options);

        $versionList = "";
        if (!empty($extensionInfo)) {
            $versionList = $this->processeExtensionVersionList($extensionInfo);
        }

        $this->logger->info("\n");
        $this->logger->writeln(wordwrap(join(', ', $versionList), 80, "\n" ));

    }
}
