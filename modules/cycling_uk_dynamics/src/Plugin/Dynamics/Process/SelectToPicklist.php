<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Select field to Dynamics Picklist property.
 *
 * @ProcessPluginAnnotation(
 *   id = "SelectToPicklist",
 *   map_from = "select",
 *   map_to = "Picklist",
 *   label = @Translation("Map Drupal Webform Select field to Dynamics Picklist property"),
 * )
 */
class SelectToPicklist implements ProcessPluginInterface {
  /**
   * Data to be transformed.
   *
   * @var mixed
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->data = $source;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination() {
    if (count($this->data) == 1) {
      return array_pop($this->data);
    }
    return $this->data;
  }

}
