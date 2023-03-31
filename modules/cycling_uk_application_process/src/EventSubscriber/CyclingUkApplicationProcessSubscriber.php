<?php

namespace Drupal\cycling_uk_application_process\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cycling_uk_application_process\Entity\CyclingUkApplicationProcess;
use Drupal\cycling_uk_application_process\Event\ApplicationStatusChanged;
use Drupal\cycling_uk_application_type\Entity\CyclingUkApplicationType;
use Drupal\cycling_uk_dynamics\Connector;
use Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueData;
use Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueLoader;
use Drupal\cycling_uk_dynamics\Event\DynamicsEntityCreatedEvent;
use Drupal\cycling_uk_dynamics\Event\PreDynamicsWebformPush;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
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
   * The dynamics queue loader service.
   *
   * @var \Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueLoader
   */
  protected CyclingUkDynamicsQueueLoader $queueLoader;


  /**
   * The dynamics queue data service.
   *
   * @var \Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueData
   */
  protected CyclingUkDynamicsQueueData $queueData;

  /**
   * Constructs a CyclingUkApplicationProcessSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueData $queue_data
   *   The queue data service.
   * @param \Drupal\cycling_uk_dynamics\CyclingUkDynamicsQueueLoader $queue_loader
   *   The queue loader service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CyclingUkDynamicsQueueData $queue_data,
    CyclingUkDynamicsQueueLoader $queue_loader
    ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueData = $queue_data;
    $this->queueLoader = $queue_loader;
  }

  /**
   * Get an Application Type by machine name.
   *
   * Machine names should be unique.
   *
   * @param string $machineName
   *   The machine name to find the Application Type by.
   *
   * @return \Drupal\cycling_uk_application_type\Entity\CyclingUkApplicationType|bool
   *   The Application Type entity or FALSE if one could not be found
   *   with the machine name provided.
   */
  protected function loadApplicationTypeByMachineName(string $machineName) {
    $applicationTypeStorage = $this->entityTypeManager
      ->getStorage('cycling_uk_application_type');
    $applicationTypeResults = $applicationTypeStorage->getQuery()
      ->condition('machine_name', $machineName)
      ->execute();
    return !empty($applicationTypeResults) ? $applicationTypeStorage->load(reset($applicationTypeResults)) : FALSE;
  }

  /**
   * Get the Application Process by Webform Submission.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webformSubmission
   *   The Webform Submission to find the Application Process by.
   *
   * @return \Drupal\cycling_uk_application_process\Entity\CyclingUkApplicationProcess|bool
   *   The Application Process entity or FALSE if one could not be found
   *   with the Webform Submission provided.
   */
  protected function loadApplicationProcessByWebformSubmission(WebformSubmission $webformSubmission) {
    $applicationProcessStorage = $this->entityTypeManager
      ->getStorage('cycling_uk_application_process');
    $applicationProcessResults = $applicationProcessStorage->getQuery()
      ->condition('webform_submission', $webformSubmission->id())
      ->execute();
    return !empty($applicationProcessResults) ? $applicationProcessStorage->load(reset($applicationProcessResults)) : FALSE;
  }

  /**
   *
   */
  protected function createApplicationProcess(WebformSubmission $webformSubmission) {
    $applicationProcessStorage = $this->entityTypeManager->getStorage('cycling_uk_application_process');
    /** @var \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface $applicationProcess */
    $applicationProcess = $applicationProcessStorage->create();
    $webform = $webformSubmission->getWebform();
    $applicationTypeKey = $webform->getThirdPartySetting(
      'cycling_uk_application',
      'type',
      FALSE
    );
    $applicationType = NULL;
    if ($applicationTypeKey) {
      $applicationType = $this->loadApplicationTypeByMachineName($applicationTypeKey);
    }
    $applicationProcess
      ->setWebformSubmission($webformSubmission);

    if ($applicationType) {
      $applicationProcess->setApplicationType($applicationType);
    }
    $applicationProcess->save();
  }

  /**
   * Does the Webform have the application handler enabled.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The Webform to check.
   */
  protected function webformHasApplicationHandler(Webform $webform) {
    return ($handler = $webform->getHandler('application_webform_handler'))
      && $handler->isEnabled();
  }

  /**
   * Convert an Application Process into Dynamics queue data.
   *
   * @param \Drupal\cycling_uk_application_process\Entity\CyclingUkApplicationProcess $applicationProcess
   *   The Application Process to convert.
   * @param string $action
   *   The action to perform.
   *
   * @return array|bool
   *   The converted queue data.
   */
  protected function getApplicationProcessQueueData(CyclingUkApplicationProcess $applicationProcess, string $action = Connector::CREATE) {
    $applicationStatus = $applicationProcess->getApplicationStatus();
    $webformSubmission = $applicationProcess->getWebformSubmission();
    $webform = $webformSubmission->getWebform();
    $applicationType = $webform->getThirdPartySetting(
      'cycling_uk_application',
      'type',
      ''
    );
    $applicationStage = $webform->getThirdPartySetting(
      'cycling_uk_application',
      'stage',
      ''
    );
    $choiceValues = [
      'ready_for_review' => '770970000',
      'awaiting_further_info' => '770970001',
      'qualified' => '770970002',
      'failed' => '770970003',
    ];
    $url = $applicationProcess->toUrl();
    $url->setOptions(['absolute' => TRUE, 'https' => TRUE]);
    return [
      'data' => [
        [
          'destination_field' => 'cuk_name',
          'destination_value' => $applicationProcess->label(),
        ],
        [
          'destination_field' => 'cuk_webformguid',
          'destination_value' => $webformSubmission->uuid(),
        ],
        [
          'destination_field' => 'cuk_applicationstage',
          'destination_value' => $applicationStage,
        ],
        [
          'destination_field' => 'cuk_applicationprogram',
          'destination_value' => $applicationType,
        ],
        [
          'destination_field' => 'cuk_applicationstatus',
          'destination_value' => $choiceValues[$applicationStatus],
        ],
        [
          'destination_field' => 'cuk_applicationlink',
          'destination_value' => $url->toString(),
        ],
        [
          'destination_field' => 'cuk_applicationguid',
          'destination_value' => $applicationProcess->uuid(),
        ],
      ],
      'drupal_entity_type' => 'cycling_uk_application_process',
      'drupal_entity_id' => $applicationProcess->id(),
      'destination_entity' => 'cuk_application',
      'destination_id' => $applicationProcess->getDynamicsId(),
      'action' => $action,
    ];
  }

  /**
   * Dynamics entity created event handler.
   *
   * If this entity is the result of an Application Process sync then store
   * the Dynamics ID on the application process so later changes to the process
   * can be pushed Dynamics.
   *
   * @param \Drupal\cycling_uk_dynamics\Event\DynamicsEntityCreatedEvent $event
   *   The dynamics entity created event.
   */
  public function onDynamicsEntityCreated(DynamicsEntityCreatedEvent $event) {

    switch ($event->getDrupalEntityType()) {

      case 'cycling_uk_application_process':
        if ($applicationProcess = CyclingUkApplicationProcess::load($event->getDrupalEntityId())) {
          $applicationProcess
            ->setDynamicsId($event->getDynamicsId())
            ->setDynamicsEntityType($event->getDynamicsEntityType())
            ->save();
        }
        break;

      case 'webform_submission':
        // Whenever a webform submission is pushed to dynamics, either
        // immediately or once the application process is qualified, we
        // queue pushing the application to dynamics.
        $webformSubmission = WebformSubmission::load($event->getDrupalEntityId());
        $applicationProcess = $this->loadApplicationProcessByWebformSubmission($webformSubmission);
        if ($applicationProcess && $this->webformHasApplicationHandler($applicationProcess->getWebformSubmission()->getWebform())) {
          $applicationQueueData = $this->getApplicationProcessQueueData($applicationProcess);
          $this->queueLoader->load([$applicationQueueData]);
        }
        break;
    }

  }

  /**
   * Application status changed event handler.
   *
   * @param \Drupal\cycling_uk_application_process\Event\ApplicationStatusChanged $event
   *   The application status changed event.
   */
  public function onApplicationStatusChanged(ApplicationStatusChanged $event) {
    /** @var \Drupal\cycling_uk_application_process\Entity\CyclingUkApplicationProcess $applicationProcess */
    $applicationProcess = $event->applicationProcess;
    $submissionMode = $applicationProcess->getApplicationType()->getSubmissionMode();
    // If the application is now qualified, assuming that flow can only happen
    // once, and the application process has no dynamics ID, assuming it hasn't
    // been pushed yet, and.
    if ($applicationProcess->hasDynamicsId()) {
      // Queue up and update to the dynamics application.
      $applicationQueueData = $this->getApplicationProcessQueueData($applicationProcess, Connector::UPDATE);
      $this->queueLoader->load([$applicationQueueData]);
    }

    if (
      $submissionMode == CyclingUkApplicationType::SUBMISSION_MODE_ON_QUALIFICATION
      && $applicationProcess->isQualified()) {
      $queueData = $this->queueData->getQueueData($applicationProcess->getWebformSubmission());
      $this->queueLoader->load($queueData);
    }

  }

  /**
   * Subscribe to the dyanmics webform push event.
   *
   * If the Webform Submission being pushed has the application handler
   * and the associated type is configured for submission, to dynamics, to
   * be on qualification then prevent this push - we will trigger the push
   * on status change.
   *
   * @param \Drupal\cycling_uk_dynamics\Event\PreDynamicsWebformPush $event
   *   The webform push event.
   *
   * @see self::onApplicationStatusChanged
   */
  public function onDynamicsWebformPush(PreDynamicsWebformPush $event) {
    $webform = $event->getWebform();
    $webform->getHandlers()->has('application_webform_handler')
      && ($handler = $webform->getHandler('application_webform_handler'))
      && $handler->isEnabled()
      && ($machineName = $webform->getThirdPartySetting('cycling_uk_application', 'type', FALSE));
    $machineName && ($applicationType = $this->loadApplicationTypeByMachineName($machineName));

    if ($applicationType && $applicationType->getSubmissionMode() == CyclingUkApplicationType::SUBMISSION_MODE_ON_QUALIFICATION) {
      $event->setshouldPush(FALSE);
    }
    // @todo move creation of application process to a webform submission insert hook.
    if ($applicationType) {
      $this->createApplicationProcess($event->getWebformSubmission());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      DynamicsEntityCreatedEvent::EVENT_NAME => ['onDynamicsEntityCreated'],
      ApplicationStatusChanged::EVENT_NAME => ['onApplicationStatusChanged'],
      PreDynamicsWebformPush::EVENT_NAME => ['onDynamicsWebformPush'],
    ];

  }

}
