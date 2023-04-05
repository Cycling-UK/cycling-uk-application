<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Number field to Dynamics Integer property.
 *
 * @ProcessPluginAnnotation(
 *   id = "NumberToFloat",
 *   map_from = "number",
 *   map_to = "Float",
 *   label = @Translation("Map Drupal Webform Number field to Dynamics Float property"),
 * )
 */
class NumberToFloat implements ProcessPluginInterface {
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
