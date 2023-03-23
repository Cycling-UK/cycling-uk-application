<?php

namespace Drupal\cycling_uk_dynamics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Event that is fired after a queue item has been created.
 */
class DynamicsQueueItemCreatedEvent extends Event {

  const EVENT_NAME = 'dynamics_queue_item_created';

  /**
   * The webform subission entity.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected ?WebformSubmissionInterface $webformSubmission = NULL;

  /**
   * The queue item data.
   *
   * @var array
   */
  protected array $queueItem;

  /**
   * Undocumented function.
   *
   * @param array $queueItem
   *   The data to be queued.
   */
  public function __construct(array $queueItem) {
    $this->queueItem = $queueItem;
  }

  /**
   * Undocumented function.
   *
   * @return \Drupal\webform\Entity\Webform
   *   The Webform entity associated with this queue item.
   */
  public function getWebform() : Webform {
    return $this->getWebformSubmission()->getWebform();
  }

  /**
   * Get the Webform Submission associated with this queue item.
   *
   * @return \Drupal\webform\Entity\WebformSubmission
   *   The Webform Submission entity associated with this queue item.
   */
  public function getWebformSubmission() : WebformSubmission {
    if (!$this->webformSubmission) {
      $this->webformSubmission = WebformSubmission::load($this->queueItem['webform_submission_id']);
    }
    return $this->webformSubmission;
  }

  /**
   * Get the queue item.
   *
   * @return array
   *   The queue item data.
   */
  public function getQueueItem() : array {
    return $this->queueItem;
  }

  /**
   * Set the queue item.
   *
   * @param array $queueItem
   *   The new queue item.
   */
  public function setQueueItem(array $queueItem) {
    $this->queueItem = $queueItem;
  }

}
