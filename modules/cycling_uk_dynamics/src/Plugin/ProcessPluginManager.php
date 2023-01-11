<?php

namespace Drupal\cycling_uk_dynamics\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for dynamics plugins.
 *
 * @see plugin_api
 */
class ProcessPluginManager extends DefaultPluginManager {

  /**
   * Construct a DynamicsProcessPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Dynamics/Process',
      $namespaces,
      $module_handler,
      'Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface',
      'Drupal\cycling_uk_dynamics\Annotation\ProcessPluginAnnotation'
    );
    $this->alterInfo('dynamics_process');
    $this->setCacheBackend($cache_backend, 'dynamics_ProcessManager');
  }

  /**
   * Returns a process plugin that can map data from source to destination.
   *
   * @param string $map_from
   *   Webform source field type.
   * @param string $map_to
   *   Dynamics destination field type.
   *
   * @return \Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface
   *   Process plugin
   */
  public function getProcessPlugin(string $map_from, string $map_to): ProcessPluginInterface {
    $pluginDefinitions = $this->getDefinitions();

    foreach ($pluginDefinitions as $pluginDefinition) {
      if ($pluginDefinition['map_from'] == $map_from && $pluginDefinition['map_to'] == $map_to) {
        // Calling on the factory is overkill here.
        return new $pluginDefinition['class']();
      }
    }

    throw new \RuntimeException('Could not find plugin');
  }

}
