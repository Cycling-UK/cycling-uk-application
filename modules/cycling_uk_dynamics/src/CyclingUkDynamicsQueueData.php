<?php

namespace Drupal\cycling_uk_dynamics;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\cycling_uk_dynamics\Event\DynamicsQueueItemCreatedEvent;
use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

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
   * Undocumented function.
   *
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ContainerAwareEventDispatcher $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
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

}
