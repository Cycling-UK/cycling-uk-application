<?php

namespace Drupal\cycling_uk_application_process;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\cycling_uk_application_type\Entity\CyclingUkApplicationType;
use Drupal\user\EntityOwnerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an interface defining a cycling uk application process entity type.
 */
interface CyclingUkApplicationProcessInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * @return bool|\Drupal\cycling_uk_application_type\Entity\CyclingUkApplicationType
   */
  public function getApplicationType();

  /**
   * @param \Drupal\cycling_uk_application_type\Entity\CyclingUkApplicationType $applicationType
   *
   * @return \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface
   */
  public function setApplicationType(CyclingUkApplicationType $applicationType) : CyclingUkApplicationProcessInterface;

  /**
   * @return \Drupal\webform\WebformSubmissionInterface
   */
  public function getWebformSubmission() : WebformSubmissionInterface;

  /**
   * @param \Drupal\webform\WebformSubmissionInterface $webform
   *
   * @return \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform) : CyclingUkApplicationProcessInterface;

  /**
   * @return string
   */
  public function getDynamicsId() : ?string;

  /**
   * @param string $dyanmicsId
   *
   * @return \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface
   */
  public function setDynamicsId(string $dyanmicsId): CyclingUkApplicationProcessInterface;

  /**
   * @return string
   */
  public function getDynamicsEntityType() : string;

  /**
   * @param string $dynamicsEntityType
   *
   * @return \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface
   */
  public function setDynamicsEntityType(string $dynamicsEntityType): CyclingUkApplicationProcessInterface;

  /**
   * @return string
   */
  public function getApplicationStatus() : string;

  /**
   * @return bool
   */
  public function isQualified() : bool;

  /**
   * @param string $applicationStatus
   *
   * @return \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface
   */
  public function setApplicationStatus(string $applicationStatus): CyclingUkApplicationProcessInterface;

}
