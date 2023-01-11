<?php

namespace Drupal\cycling_uk_application\EventSubscriber;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\cycling_uk_dynamics\Event\PreDynamicsItemQueueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Cycling UK application event subscriber.
 */
class CyclingUkApplicationSubscriber implements EventSubscriberInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a CyclingUkApplicationSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Drupal\cycling_uk_dynamics\Event\PreDynamicsItemQueueEvent $event
   *   Response event.
   */
  public function onPreDynamicsItemQueue(PreDynamicsItemQueueEvent $event) {
    $webform = $event->webform;
    try {
      $webform->getHandler('application_webform_handler');
    }
    catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $e) {
      return;
    }
    $settings = $webform->getThirdPartySettings('cycling_uk_application');
    $event->queueItem['data'][] = [
      'destination_field' => 'stage',
      'destination_value' => $settings['stage']
    ];
    $event->queueItem['data'][] = [
      'destination_field' => 'type',
      'destination_value' => $settings['type']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PreDynamicsItemQueueEvent::EVENT_NAME => ['onPreDynamicsItemQueue'],
    ];
  }

}
