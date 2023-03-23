<?php

namespace Drupal\cycling_uk_dynamics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Event that is fired before an item is added to the dynamics queue.
 */
class PreDynamicsWebformPush extends Event {

  const EVENT_NAME = 'pre_dynamics_webform_push';

  /**
   * The webform subission entity.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected WebformSubmissionInterface $webformSubmission;

  /**
   * Whether the item should be pushed.
   *
   * @var bool
   */
  protected bool $shouldPush = TRUE;

  /**
   * Undocumented function.
   *
   * @param \Drupal\webform\Entity\WebformSubmissionInterface $webform_submission
   *   The Webform we're pushing data from.
   */
  public function __construct(WebformSubmissionInterface $webform_submission) {
    $this->webformSubmission = $webform_submission;
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
   * Undocumented function.
   *
   * @return \Drupal\webform\Entity\WebformSubmission
   *   The Webform Submission entity associated with this queue item.
   */
  public function getWebformSubmission() : WebformSubmission {
    return $this->webformSubmission;
  }

  /**
   * Set whether this item should be queued.
   *
   * @param bool $shouldQueue
   *   Whether the item should be queued.
   */
  public function setshouldPush(bool $shouldQueue) {
    $this->shouldPush = $shouldQueue;
  }

  /**
   * Whether this item should be queued.
   *
   * @return bool
   *   Whether the item should be queued.
   */
  public function getshouldPush() : bool {
    return $this->shouldPush;
  }

}
