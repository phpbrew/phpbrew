<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Collector;

use Satooshi\Bundle\CoverallsV1Bundle\Config\Configuration;

/**
 * Environment variables collector for CI envrionment.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CiEnvVarsCollector
{
    /**
     * Configuration.
     *
     * @var Satooshi\Bundle\CoverallsV1Bundle\Config\Configuration
     */
    protected $config;

    /**
     * Environment variables.
     *
     * Overwritten through collection process.
     *
     * @var array
     */
    protected $env;

    /**
     * Read environment variables.
     *
     * @var array
     */
    protected $readEnv;

    /**
     * Constructor.
     *
     * @param Configuration $config Configuration.
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    // API

    /**
     * Collect environment variables.
     *
     * @param array $env $_SERVER environment.
     *
     * @return array
     */
    public function collect(array $env)
    {
        $this->env     = $env;
        $this->readEnv = array();

        $this->fillTravisCi()
        ->fillCircleCi()
        ->fillJenkins()
        ->fillLocal()
        ->fillRepoToken();

        return $this->env;
    }

    // internal method

    /**
     * Fill Travis CI environment variables.
     *
     * "TRAVIS", "TRAVIS_JOB_ID" must be set.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Collector\CiEnvVarsCollector
     */
    protected function fillTravisCi()
    {
        if (isset($this->env['TRAVIS']) && $this->env['TRAVIS'] && isset($this->env['TRAVIS_JOB_ID'])) {
            $this->env['CI_JOB_ID'] = $this->env['TRAVIS_JOB_ID'];

            if ($this->config->hasServiceName()) {
                $this->env['CI_NAME'] = $this->config->getServiceName();
            } else {
                $this->env['CI_NAME'] = 'travis-ci';
            }

            // backup
            $this->readEnv['TRAVIS']        = $this->env['TRAVIS'];
            $this->readEnv['TRAVIS_JOB_ID'] = $this->env['TRAVIS_JOB_ID'];
            $this->readEnv['CI_NAME']       = $this->env['CI_NAME'];
        }

        return $this;
    }

    /**
     * Fill CircleCI environment variables.
     *
     * "CIRCLECI", "CIRCLE_BUILD_NUM" must be set.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Collector\CiEnvVarsCollector
     */
    protected function fillCircleCi()
    {
        if (isset($this->env['CIRCLECI']) && $this->env['CIRCLECI'] && isset($this->env['CIRCLE_BUILD_NUM'])) {
            $this->env['CI_BUILD_NUMBER'] = $this->env['CIRCLE_BUILD_NUM'];
            $this->env['CI_NAME']         = 'circleci';

            // backup
            $this->readEnv['CIRCLECI']         = $this->env['CIRCLECI'];
            $this->readEnv['CIRCLE_BUILD_NUM'] = $this->env['CIRCLE_BUILD_NUM'];
            $this->readEnv['CI_NAME']          = $this->env['CI_NAME'];
        }

        return $this;
    }

    /**
     * Fill Jenkins environment variables.
     *
     * "JENKINS_URL", "BUILD_NUMBER" must be set.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Collector\CiEnvVarsCollector
     */
    protected function fillJenkins()
    {
        if (isset($this->env['JENKINS_URL']) && isset($this->env['BUILD_NUMBER'])) {
            $this->env['CI_BUILD_NUMBER'] = $this->env['BUILD_NUMBER'];
            $this->env['CI_BUILD_URL']    = $this->env['JENKINS_URL'];
            $this->env['CI_NAME']         = 'jenkins';

            // backup
            $this->readEnv['BUILD_NUMBER'] = $this->env['BUILD_NUMBER'];
            $this->readEnv['JENKINS_URL']  = $this->env['JENKINS_URL'];
            $this->readEnv['CI_NAME']      = $this->env['CI_NAME'];
        }

        return $this;
    }

    /**
     * Fill local environment variables.
     *
     * "COVERALLS_RUN_LOCALLY" must be set.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Collector\CiEnvVarsCollector
     */
    protected function fillLocal()
    {
        if (isset($this->env['COVERALLS_RUN_LOCALLY']) && $this->env['COVERALLS_RUN_LOCALLY']) {
            $this->env['CI_JOB_ID']            = null;
            $this->env['CI_NAME']              = 'php-coveralls';
            $this->env['COVERALLS_EVENT_TYPE'] = 'manual';

            // backup
            $this->readEnv['COVERALLS_RUN_LOCALLY'] = $this->env['COVERALLS_RUN_LOCALLY'];
            $this->readEnv['COVERALLS_EVENT_TYPE']  = $this->env['COVERALLS_EVENT_TYPE'];
            $this->readEnv['CI_NAME']               = $this->env['CI_NAME'];
        }

        return $this;
    }

    /**
     * Fill repo_token for unsupported CI service.
     *
     * "COVERALLS_REPO_TOKEN" must be set.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Collector\CiEnvVarsCollector
     */
    protected function fillRepoToken()
    {
        if ($this->config->hasRepoToken()) {
            $this->env['COVERALLS_REPO_TOKEN'] = $this->config->getRepoToken();
        }

        // backup
        if (isset($this->env['COVERALLS_REPO_TOKEN'])) {
            $this->readEnv['COVERALLS_REPO_TOKEN'] = $this->env['COVERALLS_REPO_TOKEN'];
        }

        return $this;
    }

    // accessor

    /**
     * Return read environment variables.
     *
     * @return array
     */
    public function getReadEnv()
    {
        return $this->readEnv;
    }
}
