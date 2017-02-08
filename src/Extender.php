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
    public function activate(Composer $composer, IOInterface $io) {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents() {
        return array(
            PackageEvents::POST_PACKAGE_INSTALL => "postInstall",
            PackageEvents::POST_PACKAGE_UPDATE => "postInstall",
        );
    }

    /**
     * @param PackageEvent $event
     * @throws \Exception
     */
    public function postInstall(PackageEvent $event) {

        $extenderManager = new ExtenderManager();
        $directory = realpath(__DIR__.'/../../../../');
        $configFile = $directory.'/console.config.yml';
        $servicesFile = $directory.'/console.services.yml';

        $extenderManager->addConfigFile($configFile);
        $extenderManager->addServicesFile($servicesFile);
        $extenderManager->processProjectPackages($directory);

        if (is_dir($directory.'/vendor/bin/drupal')) {
            $directory = $directory.'/vendor/bin/drupal';
        }

        $this->io->write('Creating cache file(s) at: ' . $directory);

        if ($configData = $extenderManager->getConfigData()) {
            file_put_contents(
                $directory . '/extend.console.config.yml',
                Yaml::dump($configData, 6, 2)
            );
        }

        if ($servicesData = $extenderManager->getServicesData()) {
            file_put_contents(
                $directory . '/extend.console.services.yml',
                Yaml::dump($servicesData, 4, 2)
            );
        }
    }
}