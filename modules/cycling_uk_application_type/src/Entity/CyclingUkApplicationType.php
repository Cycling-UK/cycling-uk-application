<?php

namespace Drupal\cycling_uk_application_type\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cycling_uk_application_type\CyclingUkApplicationTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the cycling uk application type entity class.
 *
 * @ContentEntityType(
 *   id = "cycling_uk_application_type",
 *   label = @Translation("Cycling UK application type"),
 *   label_collection = @Translation("Cycling UK application types"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cycling_uk_application_type\CyclingUkApplicationTypeListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\cycling_uk_application_type\CyclingUkApplicationTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\cycling_uk_application_type\Form\CyclingUkApplicationTypeForm",
 *       "edit" = "Drupal\cycling_uk_application_type\Form\CyclingUkApplicationTypeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "cycling_uk_application_type",
 *   admin_permission = "administer cycling uk application type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/cycling-uk-application-type/add",
 *     "canonical" = "/cycling_uk_application_type/{cycling_uk_application_type}",
 *     "edit-form" = "/admin/content/cycling-uk-application-type/{cycling_uk_application_type}/edit",
 *     "delete-form" = "/admin/content/cycling-uk-application-type/{cycling_uk_application_type}/delete",
 *     "collection" = "/admin/applications/types"
 *   },
 *   field_ui_base_route = "entity.cycling_uk_application_type.settings"
 * )
 */
class CyclingUkApplicationType extends ContentEntityBase implements CyclingUkApplicationTypeInterface {

  use EntityChangedTrait;

  public const SUBMISSION_MODE_IMMEDIATE = 'immediate';

  public const SUBMISSION_MODE_ON_QUALIFICATION = 'on_qualified';

  /**
   * {@inheritdoc}
   *
   * When a new cycling uk application type entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitleFormat() {
    return $this->get('title_format')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->get('machine_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($title) {
    $this->set('machine_name', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionMode() : string {
    return $this->get('application_submission')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the cycling uk application type'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Body'))
      ->setDescription(t('A description of the cycling uk application type.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['logo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setDescription(t('The logo for the application type'))
      ->setDisplayOptions('view', [
        'type' => 'image',
        'weight' => -4,
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['documents'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Documents'))
      ->setCardinality(-1)
      ->setSetting('file_extensions', 'pdf')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'file_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['awaiting_confirmation_email'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Awaiting confirmation email'))
      ->setDescription(t('The email body sent to the applicant when the application status is Awaiting confirmation.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['awaiting_further_info_email'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Awaiting further information email'))
      ->setDescription(t('The email body sent to the applicant when the application status is Awaiting further information.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['application_incomplete_email'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Application incomplete email'))
      ->setDescription(t('The email body sent to the applicant when the application status is Application incomplete.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['application_qualified_email'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Application qualified email'))
      ->setDescription(t('The email body sent to the applicant when the application status is Application qualified.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['email_signature'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Email signature'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title_format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Application title format'))
      ->setDescription(t('The format of the application title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the cycling uk application type is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
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

    $fields['application_submission'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Application Setting'))
      ->setSettings([
        'allowed_values' => [
          self::SUBMISSION_MODE_IMMEDIATE => 'Create web application and send immediately to Dynamics',
          self::SUBMISSION_MODE_ON_QUALIFICATION => 'Create web application and send on qualified to Dynamics',
        ],
      ])
      ->setCardinality(1)
      ->setDefaultValue('on_qualified')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the cycling uk application type author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the cycling uk application type was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the cycling uk application type was last edited.'));

    return $fields;
  }

}
