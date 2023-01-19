<?php

namespace Drupal\cycling_uk_application_process\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface;

/**
 * Event that is fired when the application process status changes.
 */
class ApplicationStatusChanged extends Event {

  const EVENT_NAME = 'application_state_changed';

  public  CyclingUkApplicationProcessInterface $applicationProcess;

  /**
   * @param \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface $applicationProcess
   */
  public function __construct(
    CyclingUkApplicationProcessInterface $applicationProcess
  ) {
    $this->applicationProcess = $applicationProcess;
  }

}
