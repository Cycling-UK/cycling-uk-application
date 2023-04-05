<?php

namespace Drupal\cycling_uk_dynamics;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\cycling_uk_dynamics\Event\DynamicsQueueItemCreatedEvent;
use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Class which provides a factory method to create dynamics queue data.
 */
class CyclingUkDynamicsQueueData {

  /**
   * Undocumented variable.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  protected Webform $webform;


  /**
   * Undocumented variable.
   *
   * @var \Drupal\webform\Entity\WebformSubmission
   */
  protected WebformSubmission $webformSubmission;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected ContainerAwareEventDispatcher $eventDispatcher;

  /**
   * Undocumented variable.
   *
   * @var array
   */
  protected array $data;

  /**
   * The elements on this Webform.
   *
   * @var array
   */
  protected array $elements;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Undocumented function.
   *
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ContainerAwareEventDispatcher $event_dispatcher, WebformElementManagerInterface $element_manager) {
    $this->eventDispatcher = $event_dispatcher;
    $this->elementManager = $element_manager;
  }

  /**
   * Create the data from the Webform submission.
   */
  public function getQueueData(WebformSubmission $webformSubmission, string $action = Connector::CREATE) : array {
    $this->webform = $webform = $webformSubmission->getWebform();
    $this->webformSubmission = $webformSubmission;
    $data = $webformSubmission->getData();
    $mappings = $webform->getThirdPartySettings('cycling_uk_dynamics');

    $queueSubmissions = [];

    foreach ($mappings as $mapping) {
      if (empty($mapping['source']) || empty($mapping['destination']) || $mapping['source'] == 'none' || $mapping['destination'] == 'none') {
        continue;
      }
      if (!$this->webformFieldExist($mapping['source'], $webform)) {
        continue;
      }

      [$destinationEntity, $destinationField] = explode(':', $mapping['destination']);

      $processPlugin = $this->getProcessPlugin($mapping['source'], $destinationEntity, $destinationField);

      $element = $this->getSourceData($mapping['source'], $data, $webform, $webformSubmission);
      $processPlugin->setSource($element);

      if (!isset($queueSubmissions[$destinationEntity])) {
        $queueSubmissions[$destinationEntity] = [
          'data' => [],
          'drupal_entity_type' => 'webform_submission',
          'drupal_entity_id' => $webformSubmission->id(),
          'destination_entity' => $destinationEntity,
          'action' => $action,
        ];
      }
      $queueSubmissions[$destinationEntity]['data'][] = [
        'destination_field' => $destinationField,
        'destination_value' => $processPlugin->getDestination(),
      ];
    }
    $header = $this->buildHeader();
    $record = $this->buildRecord($webformSubmission);
    foreach ($record as $elementIndex => $element) {
      foreach ($element['value'] as $valueIndex => $answer) {
        $queueSubmissions["cuk_webformquestion_{$elementIndex}_{$valueIndex}"] = [
          'data' => [
            [
              'destination_field' => 'cuk_name',
              'destination_value' => $header[$elementIndex][$valueIndex],
            ],
            [
              'destination_field' => 'cuk_answerraw',
              'destination_value' => $answer,
            ],
            [
              'destination_field' => 'cuk_webformguid',
              'destination_value' => $webformSubmission->uuid(),
            ],
            [
              'destination_field' => 'cuk_webformpagename',
              'destination_value' => $element['page'],
            ],
            [
              'destination_field' => 'cuk_fieldsetname',
              'destination_value' => $element['fieldset'],
            ],
          ],
          'drupal_entity_type' => 'webform_question_answer',
          'drupal_entity_id' => $webformSubmission->id(),
          'destination_entity' => 'cuk_webformquestion',
          'action' => Connector::CREATE,
        ];
      }
    }
    foreach ($queueSubmissions as $destination => $queueSubmission) {
      $queueItemCreatedEvent = new DynamicsQueueItemCreatedEvent($queueSubmission);
      $this->eventDispatcher->dispatch($queueItemCreatedEvent, DynamicsQueueItemCreatedEvent::EVENT_NAME);
      $queueSubmissions[$destination] = $queueItemCreatedEvent->getQueueItem();
    }
    return $queueSubmissions;

  }

  /**
   * Get the Webform that made the submission.
   */
  protected function getWebform() : Webform {
    return $this->webform;
  }

  /**
   * Get the Webform submission to generate the data from.
   */
  protected function getWebformSubmission() : WebformSubmission {
    return $this->webformSubmission;
  }

  /**
   * Undocumented function.
   *
   * @return \Drupal\cycling_uk_dynamics\Connector
   *   The Dynamics connector service.
   */
  protected function getConnector() : Connector {
    return \Drupal::service('cycling_uk_dynamics.connector');
  }

  /**
   * Returns a process plugin that can map data from source to destination.
   *
   * @param string $source
   *   Name of source webform field.
   * @param string $destinationEntity
   *   Name of destination Dynamics entity.
   * @param string $destinationField
   *   Name of destination Dynamics field.
   *
   * @return \Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface
   *   Process plugin
   */
  private function getProcessPlugin(string $source, string $destinationEntity, string $destinationField): ProcessPluginInterface {
    $pluginManager = \Drupal::service('plugin.manager.dynamics.process');

    $map_from_type = $this->getSourceType($source);
    $map_to_type = $this->getDestinationType($destinationEntity, $destinationField);

    return $pluginManager->getProcessPlugin($map_from_type, $map_to_type);
  }

  /**
   * Get the type of a Dynamics field.
   *
   * @param string $destinationEntity
   *   Name of entity the field is attached to.
   * @param string $destinationField
   *   Field name.
   *
   * @return string
   *   Type of field.
   */
  private function getDestinationType(string $destinationEntity, string $destinationField): string {
    $dynamicsConnector = $this->getConnector();

    $entity = $dynamicsConnector->getEntityDefinitionByLogicalName($destinationEntity);

    foreach ($entity as $value) {
      if ($value['LogicalName'] == $destinationField) {
        $map_to_type = $value['AttributeType'];
        break;
      }
    }

    if (empty($map_to_type)) {
      throw new \RuntimeException('Could not find plugin');
    }

    return $map_to_type;
  }

  /**
   * Given a field name, returns its type.
   *
   * @todo very similar code in _dynamics_get_type().
   *
   * @param string $sourceName
   *   Name of a field.
   *
   * @return string
   *   Type of $sourceName.
   */
  private function getSourceType(string $sourceName): string {
    if (strpos($sourceName, 'webform') === 0) {
      return 'textfield';
    }
    $webform = $this->getWebform();
    $elements = $webform->getElementsInitializedAndFlattened();
    $keys = explode(':', $sourceName);
    return self::getSourceTypeWorker($elements, $keys);
  }

  /**
   * Helper for self::getSourceType().
   */
  private static function getSourceTypeWorker(array $elements, array $keys) {
    $key = array_shift($keys);
    if (empty($keys)) {
      return $elements[$key]['#type'];
    }
    if (empty($elements[$key]['#webform_composite_elements'])) {
      return NULL;
    }
    return self::getSourceTypeWorker($elements[$key]['#webform_composite_elements'], $keys);
  }

  /**
   * Given a field name, returns its contents.
   *
   * @param string $sourceName
   *   Name of a field.
   * @param array $data
   *   Array of user-submitted webform data.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The Webform to generate the property data from.
   * @param \Drupal\webform\Entity\WebformSubmission $webformSubmission
   *   The Webform Submission to generate the property data from.
   *
   * @return mixed
   *   Data user submitted.
   */
  private function getSourceData(string $sourceName, array $data, Webform $webform, WebformSubmission $webformSubmission) {
    if (strpos($sourceName, 'webform') === 0) {
      return self::getWebformSourceData($sourceName, $webform, $webformSubmission);
    }
    $keys = explode(':', $sourceName);
    return self::getSourceDataWorker($keys, $data);
  }

  /**
   * Check whether a webform field exists.
   *
   * @param string $sourceName
   *   Name of a field.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The Webform to generate the property data from.
   *
   * @return bool
   *   Whether the field exists.
   */
  private function webformFieldExist(string $sourceName, Webform $webform) : bool {
    if (strpos($sourceName, 'webform') === 0) {
      return TRUE;
    }
    return (bool) $webform->getElement($sourceName);
  }

  /**
   * Helper for self::getSourceData().
   */
  private static function getSourceDataWorker(array $keys, array $data) {
    $key = array_shift($keys);
    if (empty($keys)) {
      return $data[$key];
    }
    if (empty($data[$key])) {
      return NULL;
    }
    return self::getSourceDataWorker($keys, $data[$key]);
  }

  /**
   * Get source data for 'static' webform properties.
   *
   * @param string $name
   *   The name of the propery.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The Webform to generate the property data from.
   * @param \Drupal\webform\Entity\WebformSubmission $webformSubmission
   *   The Webform Submission to generate the property data from.
   */
  private static function getWebformSourceData(string $name, Webform $webform, WebformSubmission $webformSubmission) {

    $url = $webformSubmission->toUrl();
    $url->setOptions(['absolute' => TRUE, 'https' => TRUE]);

    $data = [
      'webform_submission_url' => $url->toString(),
      'webform_submission_guid' => $webformSubmission->uuid(),
      'webform_name' => $webform->label(),
    ];
    return $data[$name] ?? NULL;
  }

  /**
   * Builds the header of the data to be exported.
   */
  protected function buildHeader() {
    $elements = $this->getElements();
    $header = [];

    // Build element columns headers.
    foreach ($elements as $element) {
      $header[] = $this->elementManager->invokeMethod('buildExportHeader', $element, $this->getExportConfig());
    }
    return $header;
  }

  /**
   * Builds the data to be exported.
   */
  protected function buildRecord(WebformSubmissionInterface $webform_submission) {
    $elements = $this->getElements();
    $record = [];
    // Build record element columns.
    foreach ($elements as $column_name => $element) {
      $element['#webform_key'] = $column_name;
      $page = $this->getPage($webform_submission, $element);
      $fieldset = $this->getFieldset($webform_submission, $element);
      $record[] = [
        'value' => $this->elementManager->invokeMethod('buildExportRecord', $element, $webform_submission, $this->getExportConfig()),
        'page' => $page ? $page['#title'] : NULL,
        'fieldset' => $fieldset ? $fieldset['#title'] : NULL,
      ];
    }
    return $record;
  }

  /**
   *
   */
  protected function getPage(WebformSubmissionInterface $webform_submission, array $element) {
    foreach ($element["#webform_parents"] as $parent) {
      $parent = $webform_submission->getWebform()->getElement($parent);
      if ($parent["#webform_plugin_id"] == 'webform_wizard_page') {
        return $parent;
      }
    }
    return NULL;
  }

  /**
   * Get the first parent which is a fieldset.
   */
  protected function getFieldset(WebformSubmissionInterface $webform_submission, array $element) {
    foreach ($element["#webform_parents"] as $parent) {
      $parent = $webform_submission->getWebform()->getElement($parent);
      if ($parent["#webform_plugin_id"] == 'fieldset') {
        return $parent;
      }
    }
  }

  /**
   * Builds the export config.
   */
  protected function getExportConfig() : array {
    return [
      'exporter' => 'delimited',
      'multiple_delimiter' => ';',
      'header_format' => 'label',
      'header_prefix' => '1',
      'header_prefix_label_delimiter' => ': ',
      'header_prefix_key_delimiter' => '__',
      'likert_answers_format' => 'label',
      'webform' => $this->getWebform(),
    ];
  }

  /**
   * Get webform elements.
   *
   * @return array
   *   An associative array containing webform elements keyed by name.
   */
  protected function getElements() {
    if (isset($this->elements)) {
      return $this->elements;
    }
    $mappings = $this->getWebform()->getThirdPartySettings('cycling_uk_dynamics');
    $mappedElements = array_filter($mappings, function ($mapping) {
      return isset($mapping['source']);
    });
    $mappedElements = array_map(function ($mapping) {
      return $mapping['source'];
    }, $mappedElements);

    $this->elements = array_filter(
      $this->getWebform()->getElementsInitializedFlattenedAndHasValue('view'),
      function ($field_id) use ($mappedElements) {
        return !in_array($field_id, $mappedElements);
      },
     ARRAY_FILTER_USE_KEY);
    // @todo exclude mapped elements
    return $this->elements;
  }

}
