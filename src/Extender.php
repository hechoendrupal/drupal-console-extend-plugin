<?php

namespace Drupal\Console\Composer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\EventDispatcher\EventSubscriberInterface;

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
    }
}