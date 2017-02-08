<?php

namespace Drupal\Console\Composer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;

class Extender implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer $composer
     */
    protected $composer;

    /**
     * @var IOInterface $io
     */
    protected $io;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents()
    {
        return array(
            PackageEvents::POST_PACKAGE_INSTALL => "processPackages",
            PackageEvents::POST_PACKAGE_UPDATE => "processPackages",
            PackageEvents::POST_PACKAGE_UNINSTALL => "processPackages",
        );
    }

    /**
     * @param PackageEvent $event
     * @throws \Exception
     */
    public function processPackages(PackageEvent $event)
    {
        $extenderManager = new ExtenderManager();
        $directory = realpath(__DIR__.'/../../../../');
        $configFile = $directory.'/console.config.yml';
        $servicesFile = $directory.'/console.services.yml';

        $extenderManager->addConfigFile($configFile);
        $extenderManager->addServicesFile($servicesFile);
        $extenderManager->processProjectPackages($directory);

        if (is_dir($directory.'/vendor/drupal/console')) {
            $directory = $directory.'/vendor/drupal/console';
        }

        $this->io->write('<info>Creating cache file(s) at: </info>' . $directory);

        if ($configData = $extenderManager->getConfigData()) {
            $configFile = $directory . '/extend.console.config.yml';
            file_put_contents(
                $configFile,
                Yaml::dump($configData, 6, 2)
            );
            $this->io->write('<info>Cache file created at: </info>' . $configFile);
        }

        if ($servicesData = $extenderManager->getServicesData()) {
            $servicesFile = $directory . '/extend.console.services.yml';
            file_put_contents(
                $servicesFile,
                Yaml::dump($servicesData, 4, 2)
            );
            $this->io->write('<info>Cache file created at: </info>' . $servicesFile);
        }
    }
}
