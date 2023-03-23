<?php

namespace Drupal\cycling_uk_application_process\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface;
use Drupal\cycling_uk_application_type\Entity\CyclingUkApplicationType;
use Drupal\cycling_uk_application_process\Event\ApplicationStatusChanged;
use Drupal\user\EntityOwnerTrait;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the cycling uk application process entity class.
 *
 * @ContentEntityType(
 *   id = "cycling_uk_application_process",
 *   label = @Translation("Cycling uk application process"),
 *   label_collection = @Translation("Cycling uk application processes"),
 *   label_singular = @Translation("cycling uk application process"),
 *   label_plural = @Translation("cycling uk application processes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count cycling uk application processes",
 *     plural = "@count cycling uk application processes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\cycling_uk_application_process\CyclingUkApplicationProcessListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\cycling_uk_application_process\CyclingUkApplicationProcessAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\cycling_uk_application_process\Form\CyclingUkApplicationProcessForm",
 *       "edit" = "Drupal\cycling_uk_application_process\Form\CyclingUkApplicationProcessForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "cycling_uk_application_process",
 *   admin_permission = "administer cycling uk application process",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/applications",
 *     "add-form" = "/cycling-uk-application-process/add",
 *     "canonical" = "/cycling-uk-application-process/{cycling_uk_application_process}",
 *     "edit-form" = "/cycling-uk-application-process/{cycling_uk_application_process}/edit",
 *     "delete-form" = "/cycling-uk-application-process/{cycling_uk_application_process}/delete",
 *   },
 *   field_ui_base_route = "entity.cycling_uk_application_process.settings",
 * )
 */
class CyclingUkApplicationProcess extends ContentEntityBase implements CyclingUkApplicationProcessInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
    if ($applicationType = $this->getApplicationType()) {
      /** @var \Drupal\webform\WebformTokenManagerInterface $webformTokenManager */
      $webformTokenManager = \Drupal::service('webform.token_manager');
      $replaced = $webformTokenManager->replace(
        $applicationType->getTitleFormat(),
        $this->getWebformSubmission()
      );
      $this->set('label', $replaced);
    }

    if (!$this->isNew()) {
      /** @var \Drupal\cycling_uk_application_process\CyclingUkApplicationProcessInterface $original */
      $original = $this->original;
      $oldStatus = $original->getApplicationStatus();
      $newStatus = $this->getApplicationStatus();
      if ($oldStatus !== $newStatus) {
        $stageChangedEvent = new ApplicationStatusChanged($this);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch($stageChangedEvent, ApplicationStatusChanged::EVENT_NAME);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformSubmission() : WebformSubmissionInterface {
    return $this->get('webform_submission')->entity;
  }

  /**
   *
   */
  protected function hasWebformSubmission() : bool {
    return !$this->get('webform_submission')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function setWebformSubmission(WebformSubmissionInterface $webformSubmission) : CyclingUkApplicationProcessInterface {
    $this->set('webform_submission', $webformSubmission->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicationType() : CyclingUkApplicationType {
    return $this->get('application_type')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setApplicationType(CyclingUkApplicationType $applicationType) : CyclingUkApplicationProcessInterface {
    $this->set('application_type', $applicationType->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDynamicsId(string $dyanmicsId): CyclingUkApplicationProcessInterface {
    $this->set('dynamics_entity_id', $dyanmicsId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicsId(): ?string {
    return $this->get('dynamics_entity_id')->value;
  }

  /**
   *
   */
  public function hasDynamicsId() : bool {
    return !empty($this->get('dynamics_entity_id')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setDynamicsEntityType(string $dynamicsEntityType): CyclingUkApplicationProcessInterface {
    $this->set('dynamics_entity_type', $dynamicsEntityType);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicsEntityType(): string {
    return $this->get('dynamics_entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setApplicationStatus(string $applicationStatus): CyclingUkApplicationProcessInterface {
    $this->set('application_status', $applicationStatus);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicationStatus(): string {
    return $this->get('application_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isQualified() : bool {
    return $this->getApplicationStatus() === 'qualified';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['webform_submission'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Webform submission'))
      ->setRequired(TRUE)
      ->setDescription(t('The Webform Submission associated with this item.'))
      ->setSetting('target_type', 'webform_submission')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['application_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Application type'))
      ->setRequired(TRUE)
      ->setDescription(t('The Application type associated with this item.'))
      ->setSetting('target_type', 'cycling_uk_application_type')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['dynamics_entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dynamics entity ID'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', FALSE);

    $fields['dynamics_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dynamics entity type'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', FALSE);

    $fields['application_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSettings([
        'allowed_values' => [
          'ready_for_review' => 'Ready for review',
          'awaiting_further_info' => 'Awaiting further information',
          'qualified' => 'Qualified',
          'failed' => 'Failed',
        ],
      ])
      ->setCardinality(1)
      ->setDefaultValue('ready_for_review')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the cycling uk application process was created.'))
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the cycling uk application process was last edited.'));

    return $fields;
  }

}
