<?php

namespace Drupal\cycling_uk_application_process\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\cycling_uk_application_process\Event\ApplicationStatusChanged;
use Drupal\cycling_uk_dynamics\Event\DynamicsEntityCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Cycling UK application process event subscriber.
 */
class CyclingUkApplicationProcessSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The dynamics queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs a CyclingUkApplicationProcessSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueueInterface $queue) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue;
  }

  /**
   * Dynamics entity created event handler.
   *
   * @param \Drupal\cycling_uk_dynamics\Event\DynamicsEntityCreatedEvent $event
   *   Response event.
   */
  public function onDynamicsEntityCreated(DynamicsEntityCreatedEvent $event) {
    $applicationProcessStorage = $this->entityTypeManager->getStorage('cycling_uk_application_process');
    /** @var \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface $applicationProcess */
    $applicationProcess = $applicationProcessStorage->create();
    $webformSubmission = $event->webformSubmission;
    $webform = $webformSubmission->getWebform();
    $applicationTypeKey = $webform->getThirdPartySetting(
      'cycling_uk_application',
      'type',
      FALSE
    );
    $applicationType = NULL;
    if ($applicationTypeKey) {
      $applicationTypeStorage = $this->entityTypeManager
        ->getStorage('cycling_uk_application_type');
      $applicationTypeResults = $applicationTypeStorage->getQuery()
        ->condition('machine_name', $applicationTypeKey)
        ->execute();
      $applicationType = !empty($applicationTypeResults) ? $applicationTypeStorage->load(reset($applicationTypeResults)) : FALSE;
    }

    $dynamicsId = $event->dynamicsId;
    $dynamicsEntityType = $event->dynamicsEntityType;
    $applicationProcess
      ->setWebformSubmission($webformSubmission)
      ->setDynamicsId($dynamicsId)
      ->setDynamicsEntityType($dynamicsEntityType);

    if ($applicationType) {
      $applicationProcess->setApplicationType($applicationType);
    }
    $applicationProcess->save();

  }

  /**
   * Application status changed event handler.
   *
   * @param \Drupal\cycling_uk_application_process\Event\ApplicationStatusChanged $event
   */
  public function onApplicationStatusChanged(ApplicationStatusChanged $event) {
    $applicationProcess = $event->applicationProcess;
    $applicationStatus = $applicationProcess->getApplicationStatus();
    $choiceValues = [
      'ready_for_review' => '770970000',
      'awaiting_further_info' => '770970001',
      'qualified' => '770970002',
      'failed' => '770970003',
    ];
    $item = [
      'action' => 'update',
      'destination_entity' => $applicationProcess->getDynamicsEntityType(),
      'destination_id' => $applicationProcess->getDynamicsId(),
      'data' => [
          [
            'destination_field' => 'cuk_applicationstatus',
            'destination_value' => $choiceValues[$applicationStatus],
          ],
      ]
    ];
    $this->queue->createItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      DynamicsEntityCreatedEvent::EVENT_NAME => ['onDynamicsEntityCreated'],
      ApplicationStatusChanged::EVENT_NAME => ['onApplicationStatusChanged'],
    ];
  }

}
