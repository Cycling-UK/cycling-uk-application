<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Textarea field to Dynamics String property.
 *
 * @ProcessPluginAnnotation(
 *   id = "TextareaToString",
 *   map_from = "textarea",
 *   map_to = "String",
 *   label = @Translation("Map Drupal Webform Textarea field to Dynamics String property"),
 * )
 */
class TextareaToString implements ProcessPluginInterface {
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
