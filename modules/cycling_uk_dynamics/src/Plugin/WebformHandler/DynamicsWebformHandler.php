<?php

namespace Drupal\cycling_uk_dynamics\Plugin\WebformHandler;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueData;
use Drupal\cycling_uk_dynamics\Event\PreDynamicsWebformPush;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dynamics webform handler.
 *
 * @WebformHandler(
 *   id = "dynamics_webform_handler",
 *   label = @Translation("Dynamics: Webform Handler"),
 *   category = @Translation("External"),
 *   description = @Translation("Queues webform submissions to be sent to Dynamics."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 *   tokens = TRUE,
 * )
 */
final class DynamicsWebformHandler extends WebformHandlerBase {

  /**
   * The queue loader.
   *
   * @var \Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueLoader
   */
  protected $queueLoader;

  /**
   * The queue data creator.
   *
   * @var \Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueData
   */
  protected CyclingUkDynamicsQueueData $queueData;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected ContainerAwareEventDispatcher $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->queueLoader = $container->get('cycling_uk_dynamics.queue_loader');
    $instance->queueData = $container->get('cycling_uk_dynamics.queue_data');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webformSubmission, $update = TRUE) {
    // We use postSave(), not submitForm(), because submitForm() runs on every
    // page submit of a multi-page form. This check is just belt and braces.
    if ($webformSubmission->getState() != WebformSubmissionInterface::STATE_COMPLETED) {
      return;
    }
    $queueData = $this->queueData->getQueueData($webformSubmission);
    $preWebformPushEvent = new PreDynamicsWebformPush($webformSubmission);
    $this->eventDispatcher->dispatch($preWebformPushEvent, PreDynamicsWebformPush::EVENT_NAME);
    if ($preWebformPushEvent->getshouldPush()) {
      $this->queueLoader->load($queueData);
    }
  }

}
