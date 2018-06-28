<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Config;

use Satooshi\Component\File\Path;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Coveralls API configurator.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Configurator
{
    // API

    /**
     * Load configuration.
     *
     * @param string         $coverallsYmlPath Path to .coveralls.yml.
     * @param string         $rootDir          Path to project root directory.
     * @param InputInterface $input|null       Input arguments.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Config\Configuration
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException If the YAML is not valid
     */
    public function load($coverallsYmlPath, $rootDir, InputInterface $input = null)
    {
        $yml     = $this->parse($coverallsYmlPath);
        $options = $this->process($yml);

        return $this->createConfiguration($options, $rootDir, $input);
    }

    // Internal method

    /**
     * Parse .coveralls.yml.
     *
     * @param string $coverallsYmlPath Path to .coveralls.yml.
     *
     * @return array
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException If the YAML is not valid
     */
    protected function parse($coverallsYmlPath)
    {
        $file = new Path();
        $path = realpath($coverallsYmlPath);

        if ($file->isRealFileReadable($path)) {
            $parser = new Parser();
            $yml = $parser->parse(file_get_contents($path));

            return empty($yml) ? array() : $yml;
        }

        return array();
    }

    /**
     * Process parsed configuration according to the configuration definition.
     *
     * @param array $yml Parsed configuration.
     *
     * @return array
     */
    protected function process(array $yml)
    {
        $processor     = new Processor();
        $configuration = new CoverallsConfiguration();

        return $processor->processConfiguration($configuration, array('coveralls' => $yml));
    }

    /**
     * Create coveralls configuration.
     *
     * @param array          $options    Processed configuration.
     * @param string         $rootDir    Path to project root directory.
     * @param InputInterface $input|null Input arguments.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Config\Configuration
     */
    protected function createConfiguration(array $options, $rootDir, InputInterface $input = null)
    {
        $configuration = new Configuration();
        $file          = new Path();

        $repoToken       = $options['repo_token'];
        $repoSecretToken = $options['repo_secret_token'];
        if ($input !== null
            && $input->hasOption('coverage_clover')
            && count($input->getOption('coverage_clover')) > 0) {
            $coverage_clover = $input->getOption('coverage_clover');
        } else {
            $coverage_clover = $options['coverage_clover'];
        }

        return $configuration
        ->setRepoToken($repoToken !== null ? $repoToken : $repoSecretToken)
        ->setServiceName($options['service_name'])
        ->setRootDir($rootDir)
        ->setCloverXmlPaths($this->ensureCloverXmlPaths($coverage_clover, $rootDir, $file))
        ->setJsonPath($this->ensureJsonPath($options['json_path'], $rootDir, $file))
        ->setExcludeNoStatements($options['exclude_no_stmt']);
    }

    /**
     * Ensure coverage_clover is valid.
     *
     * @param string $option  coverage_clover option.
     * @param string $rootDir Path to project root directory.
     * @param Path   $file    Path object.
     *
     * @return array Valid Absolute pathes of coverage_clover.
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    protected function ensureCloverXmlPaths($option, $rootDir, Path $file)
    {
        if (is_array($option)) {
            return $this->getGlobPathsFromArrayOption($option, $rootDir, $file);
        }

        return $this->getGlobPathsFromStringOption($option, $rootDir, $file);
    }

    /**
     * Return absolute paths from glob path.
     *
     * @param string $path Absolute path.
     *
     * @return array Absolute paths.
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    protected function getGlobPaths($path)
    {
        $paths    = array();
        $iterator = new \GlobIterator($path);

        foreach ($iterator as $fileInfo) {
            /* @var $fileInfo \SplFileInfo */
            $paths[] = $fileInfo->getPathname();
        }

        // validate
        if (count($paths) === 0) {
            throw new InvalidConfigurationException('coverage_clover XML file is not readable');
        }

        return $paths;
    }

    /**
     * Return absolute paths from string option value.
     *
     * @param string $option  coverage_clover option value.
     * @param string $rootDir Path to project root directory.
     * @param Path   $file    Path object.
     *
     * @return array Absolute pathes.
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    protected function getGlobPathsFromStringOption($option, $rootDir, Path $file)
    {
        if (!is_string($option)) {
            throw new InvalidConfigurationException('coverage_clover XML file is not readable');
        }

        // normalize
        $path = $file->toAbsolutePath($option, $rootDir);

        return $this->getGlobPaths($path);
    }

    /**
     * Return absolute paths from array option values.
     *
     * @param array  $options coverage_clover option values.
     * @param string $rootDir Path to project root directory.
     * @param Path   $file    Path object.
     *
     * @return array Absolute pathes.
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    protected function getGlobPathsFromArrayOption(array $options, $rootDir, Path $file)
    {
        $paths = array();

        foreach ($options as $option) {
            $paths = array_merge($paths, $this->getGlobPathsFromStringOption($option, $rootDir, $file));
        }

        return $paths;
    }

    /**
     * Ensure json_path is valid.
     *
     * @param string $option  json_path option.
     * @param string $rootDir Path to project root directory.
     * @param Path   $file    Path object.
     *
     * @return string Valid json_path.
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    protected function ensureJsonPath($option, $rootDir, Path $file)
    {
        // normalize
        $realpath = $file->getRealWritingFilePath($option, $rootDir);

        // validate file
        $realFilePath = $file->getRealPath($realpath, $rootDir);

        if ($realFilePath !== false && !$file->isRealFileWritable($realFilePath)) {
            throw new InvalidConfigurationException('json_path is not writable');
        }

        // validate parent dir
        $realDir = $file->getRealDir($realpath, $rootDir);

        if (!$file->isRealDirWritable($realDir)) {
            throw new InvalidConfigurationException('json_path is not writable');
        }

        return $realpath;
    }
}
