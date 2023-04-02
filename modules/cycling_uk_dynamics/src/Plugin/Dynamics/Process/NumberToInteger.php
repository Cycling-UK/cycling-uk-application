<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Number field to Dynamics Integer property.
 *
 * @ProcessPluginAnnotation(
 *   id = "NumberToInteger",
 *   map_from = "number",
 *   map_to = "Integer",
 *   label = @Translation("Map Drupal Webform Number field to Dynamics Integer property"),
 * )
 */
class NumberToInteger implements ProcessPluginInterface {
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
