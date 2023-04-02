<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Date field to Dynamics DateTime property.
 *
 * @ProcessPluginAnnotation(
 *   id = "DateToDateTime",
 *   map_from = "date",
 *   map_to = "DateTime",
 *   label = @Translation("Map Drupal Webform Number field to Dynamics Integer property"),
 * )
 */
class DateToDateTime implements ProcessPluginInterface {
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
