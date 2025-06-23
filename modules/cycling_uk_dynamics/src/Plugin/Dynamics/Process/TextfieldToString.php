<?php

namespace Drupal\cycling_uk_dynamics\Plugin\Dynamics\Process;

use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;

/**
 * Map Drupal Webform Textfield field to Dynamics String property.
 *
 * @ProcessPluginAnnotation(
 *   id = "TextfieldToString",
 *   map_from = "textfield",
 *   map_to = "String",
 *   label = @Translation("Map Drupal Webform Textfield field to Dynamics String property"),
 * )
 */
class TextfieldToString implements ProcessPluginInterface {
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
    $string = $this->data;
    // Check if string is a URL.
    if (filter_var($string, FILTER_VALIDATE_URL)) {
      if (mb_strlen($string) > 250) {
        // Remove query string from URL.
        $string = preg_replace('/\\?.*$/', '', $string);
      }
    }
    return $string;
  }

}
