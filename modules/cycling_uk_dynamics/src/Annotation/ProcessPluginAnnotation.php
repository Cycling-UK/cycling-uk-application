<?php

namespace Drupal\cycling_uk_dynamics\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * The annotation class for plugin.manager.dynamics.process plugins.
 *
 * @Annotation
 */
class ProcessPluginAnnotation extends Plugin {

  /**
   * The plugin's unique id.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the formatter type.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * The Drupal webform type we map from.
   *
   * @var string
   */
  public string $map_from;

  /**
   * The Dynamics AttributeType we map to.
   *
   * @var string
   */
  public string $map_to;

}
