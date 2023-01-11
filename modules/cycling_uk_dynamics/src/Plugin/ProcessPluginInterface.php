<?php

namespace Drupal\cycling_uk_dynamics\Plugin;

/**
 * Defines the common interface for all plugin.manager.dynamics.process plugins.
 *
 * @see plugin_api
 */
interface ProcessPluginInterface {

  /**
   * Set source data.
   *
   * @param mixed $source
   *   Form data.
   */
  public function setSource($source);

  /**
   * Get destination data suitable for transmitting to Dynamics.
   *
   * @return mixed
   *   Data prepared for submitting to Dynamics.
   */
  public function getDestination();

}
