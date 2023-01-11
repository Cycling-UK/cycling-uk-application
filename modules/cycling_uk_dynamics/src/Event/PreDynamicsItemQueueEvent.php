<?php

namespace Drupal\cycling_uk_dynamics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\webform\WebformInterface;

/**
 * Event that is fired before an item is added to the dynamics queue.
 */
class PreDynamicsItemQueueEvent extends Event {

  const EVENT_NAME = 'pre_dynamics_item_queue';

  /**
   * The webform object.
   *
   * @var \Drupal\webform\WebformInterface
   */
  public $webform;

  public $queueItem;

  /**
   * Constructs the object.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The Webform being queued.
   */
  public function __construct(WebformInterface $webform, array $queueItem) {
    $this->webform = $webform;
    $this->queueItem = $queueItem;
  }

}
