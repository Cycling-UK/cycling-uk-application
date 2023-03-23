<?php

namespace Drupal\cycling_uk_dynamics\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\cycling_uk_dynamics\Connector;

/**
 * Processes Tasks for Learning.
 *
 * @QueueWorker(
 *   id = "dynamics_queue",
 *   title = @Translation("Dynamics: Queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class DynamicsQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /**
     * @var \Drupal\cycling_uk_dynamics\src\Connector $dynamicsConnector
     */
    $dynamicsConnector = \Drupal::service('cycling_uk_dynamics.connector');

    switch ($data['action']) {
      case Connector::CREATE:
        $dynamicsConnector->create($data);
        break;

      case Connector::UPDATE:
        $dynamicsConnector->update($data);
        break;
    }

  }

}
