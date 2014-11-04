<?php
namespace PhpBrew;

use CLIFramework\Application;

class Console extends Application
{
    const NAME = 'phpbrew';
    const VERSION = "1.16.4";

    public function options($opts) {
        parent::options($opts);
        $opts->add('no-progress', 'Do not display progress bar.');
    }

    public function init()
    {
        parent::init();
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

        $this->command('help');

        $this->command('list-ini', 'PhpBrew\Command\ListIniCommand');

        $this->command('ctags', 'PhpBrew\Command\CtagsCommand');

        $this->command('enable', 'PhpBrew\Command\MigratedCommand');
        $this->command('install-ext', 'PhpBrew\Command\MigratedCommand');

        $this->command('self-update', 'PhpBrew\Command\SelfUpdateCommand');

        $this->command('remove');
        $this->command('purge');

        $this->command('off');
        $this->command('switch-off', 'PhpBrew\Command\SwitchOffCommand');

        $this->topics(array (
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
    }

    public function configure()
    {
        // avoid warnings when web scraping malformed HTML
        libxml_use_internal_errors(true);
        // prevent execution time limit fatal error
        set_time_limit(0);
        // prevent warnings when timezone is not set
        date_default_timezone_set(
            is_readable($tz = '/etc/timezone') ? trim(file_get_contents($tz)) : 'America/Los_Angeles'
        );
    }

    public function brief()
    {
        return 'brew your latest php!';
    }
}
