<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Radios field to Dynamics Picklist property.
 *
 * @ProcessPluginAnnotation(
 *   id = "RadiosToPicklist",
 *   map_from = "radios",
 *   map_to = "Picklist",
 *   label = @Translation("Map Drupal Webform Radios field to Dynamics Picklist property"),
 * )
 */
class RadiosToPicklist implements ProcessPluginInterface {
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
    if (!empty($this->data)) {
      return $this->data;
    }
  }

}
