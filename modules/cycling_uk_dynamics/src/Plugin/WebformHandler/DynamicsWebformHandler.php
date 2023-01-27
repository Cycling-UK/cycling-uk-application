<?php

namespace Drupal\cycling_uk_dynamics\Plugin\WebformHandler;

use Drupal\cycling_uk_dynamics\Event\PreDynamicsItemQueueEvent;
use Drupal\cycling_uk_dynamics\Plugin\ProcessPluginInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use http\Exception\RuntimeException;
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
   * The queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $dynamicsQueue;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queueFactory = $container->get('queue');

    $instance->dynamicsQueue = $queueFactory->get('dynamics_queue');

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

    $webform = $webformSubmission->getWebform();
    $data = $webformSubmission->getData();
    $mappings = $webform->getThirdPartySettings('cycling_uk_dynamics');

    $queueSubmissions = [];

    foreach ($mappings as $mapping) {
      if (empty($mapping['source']) || empty($mapping['destination']) || $mapping['source'] == 'none' || $mapping['destination'] == 'none') {
        continue;
      }

      [$destinationEntity, $destinationField] = explode(':', $mapping['destination']);

      $processPlugin = $this->getProcessPlugin($mapping['source'], $destinationEntity, $destinationField);

      $element = $this->getSourceData($mapping['source'], $data);
      $processPlugin->setSource($element);

      $queueSubmissions[$destinationEntity][] = [
        'destination_field' => $destinationField,
        'destination_value' => $processPlugin->getDestination(),
      ];
    }

    foreach ($queueSubmissions as $destinationEntity => $data) {
      $item = [
        'webform_submission_id' => $webformSubmission->id(),
        'destination_entity' => $destinationEntity,
        'action' => 'create',
        'data' => $data,
      ];
      $preQueueEvent = new PreDynamicsItemQueueEvent($webform, $item);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch($preQueueEvent, PreDynamicsItemQueueEvent::EVENT_NAME);
      $this->dynamicsQueue->createItem($preQueueEvent->queueItem);
    }
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
    /**
     * @var \Drupal\cycling_uk_dynamics\Connector $dynamicsConnector
     */
    $dynamicsConnector = \Drupal::service('cycling_uk_dynamics.connector');

    $entity = $dynamicsConnector->getEntityDefinitionByLogicalName($destinationEntity);

    foreach ($entity as $value) {
      if ($value['LogicalName'] == $destinationField) {
        $map_to_type = $value['AttributeType'];
        break;
      }
    }

    if (empty($map_to_type)) {
      throw new RuntimeException('Could not find plugin');
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
   *
   * @return mixed
   *   Data user submitted.
   */
  private function getSourceData(string $sourceName, array $data) {
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

}
