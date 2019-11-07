<?php
namespace Drupal\Console\Composer\Plugin;

use Composer\Installers\BaseInstaller;

/**
 * Class DemoInstaller
 *
 * @package Composer\Installers
 */
class DrupalConsoleInstaller extends BaseInstaller
{
  /**
   * @var array
   */
  protected $locations = array(
    'console-library' => 'vendor/drupal/{$name}/',
  );
}
