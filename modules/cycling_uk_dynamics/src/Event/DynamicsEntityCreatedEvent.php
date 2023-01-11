<?php

namespace Drupal\cycling_uk_dynamics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Event that is fired after a dynamics API create request is successful.
 */
class DynamicsEntityCreatedEvent extends Event {

  const EVENT_NAME = 'dynamics_entity_created_event';

  public string $dynamicsEntityType;

  public array $queueItem;

  public string $dynamicsId;

  public WebformSubmissionInterface $webformSubmission;

  /**
   * Construct the dyanmics entity created event.
   *
   * @param string $dynamicsEntityType
   * @param int $dynamicsId
   * @param \Drupal\webform\$webformSubmission $webformSubmission
   * @param array $queueItem
   */
  public function __construct(
    string $dynamicsEntityType,
    string $dynamicsId,
    WebformSubmissionInterface $webformSubmission,
    array $queueItem
  ) {
    $this->dynamicsId = $dynamicsId;
    $this->queueItem = $queueItem;
    $this->dynamicsEntityType = $dynamicsEntityType;
    $this->webformSubmission = $webformSubmission;
  }

}
