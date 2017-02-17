<?php

namespace Drupal\Console\Composer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;

// Explicitly require ExtenderManager here.
// When this package is uninstalled, ExtenderManager needs to be available any
// time this class is available.
require_once 'ExtenderManager.php';

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

        $composer = $event->getComposer();
        $installationManager = $composer->getInstallationManager();
        $repositoryManager = $composer->getRepositoryManager();
        $localRepository = $repositoryManager->getLocalRepository();

        foreach ($localRepository->getPackages() as $package) {
          if ($installationManager->isPackageInstalled($localRepository, $package)) {
            if ($package->getType() === 'drupal-console-library') {
              $extenderManager->addConfigFile($installationManager->getInstallPath($package) . '/console.services.yml');
              $extenderManager->addConfigFile($installationManager->getInstallPath($package) . '/console.config.yml');
            }
          }
        }

        if ($consolePackage = $localRepository->findPackage('drupal/console', '*')) {
          if ($localRepository->hasPackage($consolePackage)) {
            $directory = $installationManager->getInstallPath($consolePackage);
          }
        }
        if (empty($directory)) {
          // cwd should be the project root.  This is the same logic Symfony uses.
          $directory = getcwd();
        }

        $configFile = $directory . '/extend.console.config.yml';
        $servicesFile = $directory . '/extend.console.services.yml';
        $servicesUninstallFile = $directory . '/extend.console.uninstall.services.yml';

        if (file_exists($configFile)) {
            unlink($configFile);
            $this->io->write('<info>Removing config cache file:</info>' . $configFile);
        }

        if (file_exists($servicesFile)) {
            unlink($servicesFile);
            $this->io->write('<info>Removing services cache file:</info>' . $servicesFile);
        }

        if (file_exists($servicesUninstallFile)) {
            unlink($servicesUninstallFile);
            $this->io->write('<info>Removing services cache file:</info>' . $servicesUninstallFile);
        }

        if ($configData = $extenderManager->getConfigData()) {
            file_put_contents(
                $configFile,
                Yaml::dump($configData, 6, 2)
            );
            $this->io->write('<info>Creating config cache file:</info>' . $configFile);
        }

        $servicesData = $extenderManager->getServicesData();
        if ($servicesData && array_key_exists('install', $servicesData)) {
            file_put_contents(
                $servicesFile,
                Yaml::dump($servicesData['install'], 4, 2)
            );
            $this->io->write('<info>Creating services cache file: </info>' . $servicesFile);
        }

        $servicesData = $extenderManager->getServicesData();
        if ($servicesData && array_key_exists('uninstall', $servicesData)) {
            file_put_contents(
                $servicesUninstallFile,
                Yaml::dump($servicesData['uninstall'], 4, 2)
            );
            $this->io->write('<info>Creating services cache file: </info>' . $servicesUninstallFile);
        }
    }
}
