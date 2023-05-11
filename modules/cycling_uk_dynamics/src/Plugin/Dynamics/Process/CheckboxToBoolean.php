<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Checkbox field to Dynamics Boolean property.
 *
 * @ProcessPluginAnnotation(
 *   id = "CheckboxToBoolean",
 *   map_from = "checkbox",
 *   map_to = "Boolean",
 *   label = @Translation("Map Drupal Webform Checkbox field to Dynamics Boolean property"),
 * )
 */
class CheckboxToBoolean implements ProcessPluginInterface {
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
      return (bool) $this->data;
    }
  }

}
