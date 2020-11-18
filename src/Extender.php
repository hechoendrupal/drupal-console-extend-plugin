<?php

namespace Drupal\Console\Composer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

// Explicitly require ExtenderManager here.
// When this package is uninstalled, ExtenderManager needs to be available any
// time this class is available.
require_once __DIR__ . '/ExtenderManager.php';

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

      $installer = new Installer($io, $composer);

      $composer->getInstallationManager()->addInstaller($installer);

    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => "processPackages",
            ScriptEvents::POST_UPDATE_CMD => "processPackages",
        ];
    }

    /**
     * @param Event $event
     * @throws \Exception
     */
    public function processPackages(Event $event)
    {
        $extenderManager = new ExtenderManager();

        $composer = $event->getComposer();
        $installationManager = $composer->getInstallationManager();
        $repositoryManager = $composer->getRepositoryManager();
        $localRepository = $repositoryManager->getLocalRepository();

        foreach ($localRepository->getPackages() as $package) {
            if ($installationManager->isPackageInstalled($localRepository, $package)) {
                if ($package->getType() === 'drupal-console-library') {
                    $extenderManager->addServicesFile($installationManager->getInstallPath($package) . '/console.services.yml');
                    $extenderManager->addConfigFile($installationManager->getInstallPath($package) . '/console.config.yml');
                }
            }
        }

        /*
        if ($consolePackage = $localRepository->findPackage('drupal/console', '*')) {
            if ($localRepository->hasPackage($consolePackage)) {
                $directory = $installationManager->getInstallPath($consolePackage);
            }
        }
        */

        if (empty($directory)) {
            // cwd should be the project root.  This is the same logic Symfony uses.
            $directory = getcwd();
        }

        $configFile = $directory . '/extend.console.config.yml';
        $servicesFile = $directory . '/extend.console.services.yml';
        $servicesUninstallFile = $directory . '/extend.console.uninstall.services.yml';

        if (file_exists($configFile)) {
            unlink($configFile);
            $this->io->write('<info>Removing config cache file:</info>');
            $this->io->write($configFile);
        }

        if (file_exists($servicesFile)) {
            unlink($servicesFile);
            $this->io->write('<info>Removing packages services cache file:</info>');
            $this->io->write($servicesFile);
        }

        if (file_exists($servicesUninstallFile)) {
            unlink($servicesUninstallFile);
            $this->io->write('<info>Removing packages services cache file:</info>');
            $this->io->write($servicesUninstallFile);
        }

        if ($configData = $extenderManager->getConfigData()) {
            file_put_contents(
                $configFile,
                Yaml::dump($configData, 6, 2)
            );
            $this->io->write('<info>Creating packages config cache file:</info>');
            $this->io->write($configFile);
        }

        $servicesData = $extenderManager->getServicesData();
        if ($servicesData && array_key_exists('install', $servicesData)) {
            file_put_contents(
                $servicesFile,
                Yaml::dump($servicesData['install'], 4, 2)
            );
            $this->io->write('<info>Creating packages services cache file: </info>');
            $this->io->write($servicesFile);
        }

        $servicesData = $extenderManager->getServicesData();
        if ($servicesData && array_key_exists('uninstall', $servicesData)) {
            file_put_contents(
                $servicesUninstallFile,
                Yaml::dump($servicesData['uninstall'], 4, 2)
            );
            $this->io->write('<info>Creating packages services cache file: </info>');
            $this->io->write($servicesUninstallFile);
        }

        $this->removeCacheFiles($directory);
    }

    protected function removeCacheFiles($directory)
    {
        try {
            $finder = new Finder();
            $finder->files()
                ->in($directory)
                ->name('*-console.services.yml')
                ->ignoreUnreadableDirs();

            foreach ($finder as $file) {
                $this->io->write('<info>Removing site services cache file:</info>');
                $this->io->write($file->getPathName());
                unlink($file->getPathName());
            }
        } catch (\InvalidArgumentException $argumentException) {
            $this->io->write('<info>Cache file can not be deleted</info>');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Nothing to deactivate.
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Nothing to uninstall.
    }	
}
