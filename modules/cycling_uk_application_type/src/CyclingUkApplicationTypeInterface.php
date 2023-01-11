<?php

namespace Drupal\cycling_uk_application_type;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a cycling uk application type entity type.
 */
interface CyclingUkApplicationTypeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the cycling uk application type title.
   *
   * @return string
   *   Title of the cycling uk application type.
   */
  public function getTitle();

  /**
   * Sets the cycling uk application type title.
   *
   * @param string $title
   *   The cycling uk application type title.
   *
   * @return \Drupal\cycling_uk_application_type\CyclingUkApplicationTypeInterface
   *   The called cycling uk application type entity.
   */
  public function setTitle($title);


  /**
   * Gets the cycling uk application type title format.
   *
   * @return string
   *   Title format of the cycling uk application type.
   */
  public function getTitleFormat();

  /**
   * Gets the cycling uk application type creation timestamp.
   *
   * @return int
   *   Creation timestamp of the cycling uk application type.
   */
  public function getCreatedTime();

  /**
   * Sets the cycling uk application type creation timestamp.
   *
   * @param int $timestamp
   *   The cycling uk application type creation timestamp.
   *
   * @return \Drupal\cycling_uk_application_type\CyclingUkApplicationTypeInterface
   *   The called cycling uk application type entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the cycling uk application type status.
   *
   * @return bool
   *   TRUE if the cycling uk application type is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the cycling uk application type status.
   *
   * @param bool $status
   *   TRUE to enable this cycling uk application type, FALSE to disable.
   *
   * @return \Drupal\cycling_uk_application_type\CyclingUkApplicationTypeInterface
   *   The called cycling uk application type entity.
   */
  public function setStatus($status);

}
