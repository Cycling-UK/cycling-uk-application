<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Url field to Dynamics String property.
 *
 * @ProcessPluginAnnotation(
 *   id = "UrlToString",
 *   map_from = "url",
 *   map_to = "String",
 *   label = @Translation("Map Drupal Webform Url field to Dynamics String property"),
 * )
 */
class UrlToString implements ProcessPluginInterface {
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
    return $this->data;
  }

}
