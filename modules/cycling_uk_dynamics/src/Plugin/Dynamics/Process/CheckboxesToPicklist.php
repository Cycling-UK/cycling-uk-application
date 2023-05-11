<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Checkboxes field to Dynamics Picklist property.
 *
 * @ProcessPluginAnnotation(
 *   id = "CheckboxesToPicklist",
 *   map_from = "checkboxes",
 *   map_to = "Picklist",
 *   label = @Translation("Map Drupal Webform Checkboxes field to Dynamics Picklist property"),
 * )
 */
class CheckboxesToPicklist implements ProcessPluginInterface {
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
      if (count($this->data) == 1) {
        return array_pop($this->data);
      }
      return $this->data;
    }  
  }

}
