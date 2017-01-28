<?php

namespace PhpBrew;

use CLIFramework\Application;
use CLIFramework\Exception\CommandNotFoundException;
use CLIFramework\Exception\CommandArgumentNotEnoughException;
use CLIFramework\ExceptionPrinter\ProductionExceptionPrinter;
use CLIFramework\ExceptionPrinter\DevelopmentExceptionPrinter;
use Exception;
use BadMethodCallException;
use PhpBrew\Exception\SystemCommandException;

class Console extends Application
{
    const NAME = 'phpbrew';
    const VERSION = '1.22.6';

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('no-progress', 'Do not display progress bar.');
    }

    public function init()
    {
        parent::init();
        $this->command('app');
        $this->command('init');
        $this->command('known');
        $this->command('install');
        $this->command('list');
        $this->command('use');
        $this->command('switch');
        $this->command('each');

        $this->command('config');
        $this->command('info');
        $this->command('env');
        $this->command('extension');
        $this->command('variants');
        $this->command('path');
        $this->command('cd');
        $this->command('download');
        $this->command('clean');
        $this->command('update');
        $this->command('ctags');
        $this->command('help');

        $this->command('fpm');

        $this->command('list-ini', 'PhpBrew\Command\ListIniCommand');
        $this->command('self-update', 'PhpBrew\Command\SelfUpdateCommand');

        $this->command('remove');
        $this->command('purge');

        $this->command('off');
        $this->command('switch-off', 'PhpBrew\Command\SwitchOffCommand');

        $this->topics(array(
            'contribution' => 'PhpBrew\\Topic\\ContributionTopic',
            'cookbook' => 'PhpBrew\\Topic\\CookbookTopic',
            'home' => 'PhpBrew\\Topic\\HomeTopic',
            'migrating-from-homebrew-php-to-phpbrew' => 'PhpBrew\\Topic\\MigratingFromHomebrewPhpToPhpbrewTopic',
            'phpbrew-ja' => 'PhpBrew\\Topic\\PHPBrewJATopic',
            'release-process' => 'PhpBrew\\Topic\\ReleaseProcessTopic',
            'requirement' => 'PhpBrew\\Topic\\RequirementTopic',
            'setting-up-configuration' => 'PhpBrew\\Topic\\SettingUpConfigurationTopic',
            'troubleshooting' => 'PhpBrew\\Topic\\TroubleshootingTopic',
        ));

        $this->configure();

        // We use '#' as the prefix to prevent issue with bash
        if (!extension_loaded('json')) {
            $this->logger->warn('# WARNING: json extension is required for downloading release info.');
        }
        if (!extension_loaded('libxml')) {
            $this->logger->warn('# WARNING: libxml extension is required for parsing pecl package file.');
        }
        if (!extension_loaded('curl')) {
            $this->logger->warn('# WARNING: curl extension might be required for fetching data.');
        }
        if (!extension_loaded('ctype')) {
            $this->logger->warn('# WARNING: ctype extension might be required for parsing yaml file.');
        }

        if (Utils::isRootlessEnabled()) {
            $this->logger->warn('#WARNING: it seems you are running PHPBrew under MacOS 10.11 or above with rootless enabled. ' .
                'it\'s recommended turn off rootless before your continue, or you may experience some issues.');
        }
    }

    public function configure()
    {
        // avoid warnings when web scraping possible malformed HTML from pecl
        if (extension_loaded('libxml')) {
            libxml_use_internal_errors(true);
        }
        // prevent execution time limit fatal error
        set_time_limit(0);

        // prevent warnings when timezone is not set
        date_default_timezone_set(Utils::readTimeZone() ?: 'America/Los_Angeles');

        // fix bold output so it looks good on light and dark terminals
        $this->getFormatter()->addStyle('bold', array('bold' => 1));

        $this->logger->levelStyles['warn'] = 'yellow';
        $this->logger->levelStyles['error'] = 'red';
    }

    public function brief()
    {
        return 'brew your latest php!';
    }

    public function runWithTry(array $argv)
    {
        try {
            return $this->run($argv);
        } catch (CommandArgumentNotEnoughException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->writeln('Expected argument prototypes:');
            foreach ($e->getCommand()->getAllCommandPrototype() as $p) {
                $this->logger->writeln("\t".$p);
            }
            $this->logger->newline();
        } catch (CommandNotFoundException $e) {
            $this->logger->error($e->getMessage().' available commands are: '.implode(', ', $e->getCommand()->getVisibleCommandList()));
            $this->logger->newline();

            $this->logger->writeln('Please try the command below to see the details:');
            $this->logger->newline();
            $this->logger->writeln("\t".$this->getProgramName().' help ');
            $this->logger->newline();
        } catch (SystemCommandException $e) {

            // Todo: detect $lastline for library missing here...

            $buildLog = $e->getLogFile();
            $this->logger->error('Error: '.trim($e->getMessage()));

            if (file_exists($buildLog)) {
                $this->logger->error('The last 5 lines in the log file:');
                $lines = array_slice(file($buildLog), -5);
                foreach ($lines as $line) {
                    echo $line , PHP_EOL;
                }
                $this->logger->error('Please checkout the build log file for more details:');
                $this->logger->error("\t tail $buildLog");
            }
        } catch (BadMethodCallException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error('Seems like an application logic error, please contact the developer.');
        } catch (Exception $e) {
            if ($this->options && $this->options->debug) {
                $printer = new DevelopmentExceptionPrinter($this->getLogger());
                $printer->dump($e);
            } else {
                $printer = new ProductionExceptionPrinter($this->getLogger());
                $printer->dump($e);
            }
        }

        return false;
    }
}
