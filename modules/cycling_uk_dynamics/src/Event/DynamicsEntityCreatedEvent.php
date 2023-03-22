<?php

namespace Drupal\cycling_uk_dynamics\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired after a dynamics API create request is successful.
 */
class DynamicsEntityCreatedEvent extends Event {

  const EVENT_NAME = 'dynamics_entity_created_event';

  /**
   * Undocumented variable.
   *
   * @var string
   */

  protected string $drupalEntityType;

  /**
   * Undocumented variable.
   *
   * @var int
   */
  protected int $drupalEntityId;

  /**
   * Undocumented variable.
   *
   * @var string
   */
  protected string $dynamicsEntityType;

  /**
   * Undocumented variable.
   *
   * @var string
   */
  protected string $dynamicsId;

  /**
   * Construct the dynamics entity created event.
   */
  public function __construct(
    string $drupalEntityType,
    int $drupalEntityId,
    string $dynamicsEntityType,
    string $dynamicsId
  ) {
    $this->dynamicsId = $dynamicsId;
    $this->dynamicsEntityType = $dynamicsEntityType;
    $this->drupalEntityId = $drupalEntityId;
    $this->drupalEntityType = $drupalEntityType;
  }

  /**
   * Get the Drupal entity ID.
   */
  public function getDrupalEntityId() : int {
    return $this->drupalEntityId;
  }

  /**
   * Get the Drupal entity type.
   */
  public function getDrupalEntityType() : string {
    return $this->drupalEntityType;
  }

  /**
   * Get the Dynamics ID.
   */
  public function getDynamicsId() : string {
    return $this->dynamicsId;
  }

  /**
   * Get the Dynamics entity type.
   */
  public function getDynamicsEntityType() : string {
    return $this->dynamicsEntityType;
  }

}
