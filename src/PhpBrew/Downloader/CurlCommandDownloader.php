<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 11:57
 */

namespace PhpBrew\Downloader;


use PhpBrew\Utils;

class CurlCommandDownloader extends BaseDownloader
{
    protected $enableContinueAt = false;

    public function enableContinueAtOption()
    {
        $this->enableContinueAt = true;
    }

    protected function process($url, $targetFilePath)
    {
        $this->logger->info('downloading via curl command');
        //todo proxy setting
        $silent = $this->logger->isQuiet() ? '--silent ' : '';
        $command = array("curl");

        if ($this->enableContinueAt || $this->options->{'continue'}) {
            $command[] = "-C -";
        }
        $command[] = "-L";
        if ($this->logger->isQuiet()) {
            $command[] = '--silent';
        }
        $command[] = "-o";
        $command[] = escapeshellarg($targetFilePath);
        $command[] = escapeshellarg($url);
        $cmd = join(' ', $command);
        $this->logger->debug($cmd);
        Utils::system($cmd);
        return true;
    }

    public function hasSupport($requireSsl)
    {
        return Utils::findbin('curl');
    }
}
