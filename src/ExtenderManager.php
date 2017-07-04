<?php

namespace Drupal\Console\Composer\Plugin;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

class ExtenderManager
{
    /**
     * @var array
     */
    protected $configData = [];

    /**
     * @var array
     */
    protected $servicesData = [];

    /**
     * ExtendExtensionManager constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @param string $composerFile
     *
     * @return bool
     */
    public function isValidPackageType($composerFile)
    {
        if (!is_file($composerFile)) {
            return false;
        }

        $composerContent = json_decode(file_get_contents($composerFile), true);
        if (!$composerContent) {
            return false;
        }

        if (!array_key_exists('type', $composerContent)) {
            return false;
        }

        return $composerContent['type'] === 'drupal-console-library';
    }

    /**
     * @param string $configFile
     */
    public function addConfigFile($configFile)
    {
        $configData = $this->parseData($configFile);
        if ($this->isValidConfigData($configData)) {
            $this->configData = array_merge_recursive(
                $configData,
                $this->configData
            );
        }
    }

    /**
     * @param string $servicesFile
     */
    public function addServicesFile($servicesFile)
    {
        $consoleTags = [
            'drupal.command',
            'drupal.generator'
        ];
        $servicesData = $this->parseData($servicesFile);
        if ($this->isValidServicesData($servicesData)) {
            foreach ($servicesData['services'] as $key => $definition) {
                if (!array_key_exists('tags', $definition)) {
                    continue;
                }
                $bootstrap = 'install';
                foreach ($definition['tags'] as $tags) {
                    if (!array_key_exists('name', $tags)) {
                        $bootstrap = null;
                        continue;
                    }
                    if (array_search($tags['name'], $consoleTags) === false) {
                        $bootstrap = null;
                        continue;
                    }
                    if (array_key_exists('bootstrap', $tags)) {
                        $bootstrap = $tags['bootstrap'];
                    }
                }
                if ($bootstrap) {
                    $this->servicesData[$bootstrap]['services'][$key] = $definition;
                }
            }
        }
    }

    /**
     * init
     */
    private function init()
    {
        $this->configData = [];
        $this->servicesData = [];
    }

    /**
     * @param $file
     * @return array|mixed
     */
    private function parseData($file)
    {
        if (!file_exists($file)) {
            return [];
        }

        $data = Yaml::parse(
            file_get_contents($file)
        );

        if (!$data) {
            return [];
        }

        return $data;
    }

    public function processProjectPackages($directory)
    {
        $finder = new Finder();
        $finder->files()
            ->name('composer.json')
            ->contains('drupal-console-library')
            ->in($directory)
            ->ignoreUnreadableDirs();

        foreach ($finder as $file) {
            $this->processComposerFile($file->getPathName());
        }
    }

    /**
     * @param $composerFile
     */
    private function processComposerFile($composerFile)
    {
        $packageDirectory = dirname($composerFile);

        $configFile = $packageDirectory.'/console.config.yml';
        $this->addConfigFile($configFile);

        $servicesFile = $packageDirectory.'/console.services.yml';
        $this->addServicesFile($servicesFile);
    }

    /**
     * @param array $configData
     *
     * @return boolean
     */
    private function isValidConfigData($configData)
    {
        if (!$configData) {
            return false;
        }

        if (!array_key_exists('application', $configData)) {
            return false;
        }

        if (!array_key_exists('autowire', $configData['application'])) {
            return false;
        }

        if (!array_key_exists('commands', $configData['application']['autowire'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array $servicesData
     *
     * @return boolean
     */
    private function isValidServicesData($servicesData)
    {
        if (!$servicesData) {
            return false;
        }

        if (!array_key_exists('services', $servicesData)) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getConfigData()
    {
        return $this->configData;
    }

    /**
     * @return array
     */
    public function getServicesData()
    {
        return $this->servicesData;
    }
}
