<?php

namespace Drupal\Console\Composer\Plugin;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installers\Installer as BaseInstaller;

class Installer extends BaseInstaller
{

  /**
   * Package types to installer class map
   *
   * @var array
   */
  private $supportedTypes = array(
    'drupal' => 'DrupalConsoleInstaller'
  );

  /**
   * {@inheritDoc}
   */
  public function getInstallPath(PackageInterface $package)
  {
    $type = $package->getType();
    $frameworkType = $this->findFrameworkType($type);

    if ($frameworkType === false) {
      throw new \InvalidArgumentException(
        'Sorry the package type of this package is not yet supported.'
      );
    }

    $class = 'Drupal\\Console\\Composer\\Plugin\\' . $this->supportedTypes[$frameworkType];
    $installer = new $class($package, $this->composer, $this->getIO());

    return $installer->getInstallPath($package, $frameworkType);
  }

  /**
   * Get the second part of the regular expression to check for support of a
   * package type
   *
   * @param  string $frameworkType
   * @return string
   */
  protected function getLocationPattern($frameworkType)
  {
    $pattern = false;
    if (!empty($this->supportedTypes[$frameworkType])) {
      $frameworkClass = 'Drupal\\Console\\Composer\\Plugin\\' . $this->supportedTypes[$frameworkType];
      /** @var BaseInstaller $framework */
      $framework = new $frameworkClass(null, $this->composer, $this->getIO());
      $locations = array_keys($framework->getLocations());
      $pattern = $locations ? '(' . implode('|', $locations) . ')' : false;
    }

    return $pattern ? : '(\w+)';
  }

  /**
   * Get I/O object
   *
   * @return IOInterface
   */
  private function getIO()
  {
    return $this->io;
  }
}
