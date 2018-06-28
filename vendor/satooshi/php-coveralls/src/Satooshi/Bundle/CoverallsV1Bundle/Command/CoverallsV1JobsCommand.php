<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Command;

use Satooshi\Bundle\CoverallsV1Bundle\Api\Jobs;
use Satooshi\Bundle\CoverallsV1Bundle\Config\Configuration;
use Satooshi\Bundle\CoverallsV1Bundle\Config\Configurator;
use Satooshi\Bundle\CoverallsV1Bundle\Repository\JobsRepository;
use Satooshi\Component\Log\ConsoleLogger;
use Satooshi\Component\File\Path;
use Guzzle\Http\Client;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Coveralls Jobs API v1 command.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CoverallsV1JobsCommand extends Command
{
    /**
     * Path to project root directory.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    // internal method

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('coveralls:v1:jobs')
        ->setDescription('Coveralls Jobs API v1')
        ->addOption(
            'config',
            '-c',
            InputOption::VALUE_OPTIONAL,
            '.coveralls.yml path',
            '.coveralls.yml'
        )
        ->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Do not send json_file to Jobs API'
        )
        ->addOption(
            'exclude-no-stmt',
            null,
            InputOption::VALUE_NONE,
            'Exclude source files that have no executable statements'
        )
        ->addOption(
            'env',
            '-e',
            InputOption::VALUE_OPTIONAL,
            'Runtime environment name: test, dev, prod',
            'prod'
        )
        ->addOption(
            'coverage_clover',
            '-x',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Coverage clover xml files(allowing multiple values).',
            array()
        )
        ->addOption(
            'root_dir',
            '-r',
            InputOption::VALUE_OPTIONAL,
            'Root directory of the project.',
            '.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start(__CLASS__);
        $file = new Path();
        if ($input->getOption('root_dir') !== '.') {
            $this->rootDir = $file->toAbsolutePath(
                $input->getOption('root_dir'),
                $this->rootDir
            );
        }

        $config = $this->loadConfiguration($input, $this->rootDir);
        $this->logger = $config->isVerbose() && !$config->isTestEnv() ? new ConsoleLogger($output) : new NullLogger();

        $this->executeApi($config);

        $event = $stopwatch->stop(__CLASS__);
        $time  = number_format($event->getDuration() / 1000, 3);        // sec
        $mem   = number_format($event->getMemory() / (1024 * 1024), 2); // MB
        $this->logger->info(sprintf('elapsed time: <info>%s</info> sec memory: <info>%s</info> MB', $time, $mem));

        return 0;
    }

    // for Jobs API

    /**
     * Load configuration.
     *
     * @param InputInterface $input   Input arguments.
     * @param string         $rootDir Path to project root directory.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Config\Configuration
     */
    protected function loadConfiguration(InputInterface $input, $rootDir)
    {
        $coverallsYmlPath = $input->getOption('config');

        $ymlPath      = $this->rootDir . DIRECTORY_SEPARATOR . $coverallsYmlPath;
        $configurator = new Configurator();

        return $configurator
            ->load($ymlPath, $rootDir, $input)
            ->setDryRun($input->getOption('dry-run'))
            ->setExcludeNoStatementsUnlessFalse($input->getOption('exclude-no-stmt'))
            ->setVerbose($input->getOption('verbose'))
            ->setEnv($input->getOption('env'));
    }

    /**
     * Execute Jobs API.
     *
     * @param Configuration $config Configuration.
     */
    protected function executeApi(Configuration $config)
    {
        $client     = new Client();
        $api        = new Jobs($config, $client);
        $repository = new JobsRepository($api, $config);

        $repository->setLogger($this->logger);
        $repository->persist();
    }

    // accessor

    /**
     * Set root directory.
     *
     * @param string $rootDir Path to project root directory.
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
    }
}
