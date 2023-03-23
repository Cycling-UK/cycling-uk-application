<?php

namespace Drupal\cycling_uk_dynamics;

use Drupal\Core\Queue\QueueInterface;

/**
 * The dynamics queue loader service.
 */
class CyclingUkDynamicsQueueLoader {

  /**
   * The dyanmics queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * The queue loader constructor.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The dynamics queue.
   */
  public function __construct(QueueInterface $queue) {
    $this->queue = $queue;
  }

  /**
   * Load the queue items.
   *
   * @param array $items
   *   The queue items to load.
   */
  public function load(array $items) {
    foreach ($items as $item) {
      $this->queue->createItem($item);
    }

  }

}
