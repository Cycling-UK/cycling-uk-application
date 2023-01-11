<?php

namespace Drupal\cycling_uk_application_process\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cycling_uk_dynamics\Event\DynamicsEntityCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
   * Constructs a CyclingUkApplicationProcessSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Kernel response event handler.
   *
   * @param \Drupal\cycling_uk_dynamics\Event\DynamicsEntityCreatedEvent $event
   *   Response event.
   */
  public function onDynamicsEntityCreated(DynamicsEntityCreatedEvent $event) {
    $applicationProcessStorage = $this->entityTypeManager->getStorage('cycling_uk_application_process');
    /** @var \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface $applicationProcess */
    $applicationProcess = $applicationProcessStorage->create();
    $webformSubmission = $event->webformSubmission;
    $dynamicsId = $event->dynamicsId;
    $applicationProcess
      ->setWebformSubmission($webformSubmission)
      ->setDynamicsId($dynamicsId)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      DynamicsEntityCreatedEvent::EVENT_NAME => ['onDynamicsEntityCreated'],
    ];
  }

}
