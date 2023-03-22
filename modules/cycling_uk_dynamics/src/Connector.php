<?php

namespace Drupal\cycling_uk_dynamics;

use AlexaCRM\WebAPI\Client;
use AlexaCRM\WebAPI\ClientFactory;
use AlexaCRM\Xrm\Entity;
use Doctrine\Common\Annotations\AnnotationException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\cycling_uk_dynamics\Event\DynamicsEntityCreatedEvent;

/**
 * Connector service handles communication between us and Dynamics.
 */
class Connector {

  public const UPDATE = 'update';

  public const CREATE = 'create';

  const CACHE_TAG = 'dynamics_query';

  /**
   * Eg 'https://cyclingukdev.crm11.dynamics.com/'.
   *
   * @var string
   */
  protected string $instanceUri;

  /**
   * Eg '6d05b2be-24c0-4eac-90b1-f72b78dd6d1a'.
   *
   * @var string
   */
  protected string $applicationId;

  /**
   * Eg 'qYI7Q~UPbA5uvBPmCkSJ1EH9aIWq-Ng-ArpRc'.
   *
   * @var string
   */
  protected string $applicationSecret;

  /**
   * Eg 'https://cyclingukdev.api.crm11.dynamics.com/api/data/v9.2/'.
   *
   * @var string
   */
  protected string $endpoint;

  /**
   * Eg ['lead', 'contact', 'opportunity'].
   *
   * @var array
   */
  protected array $entityWhitelist;

  /**
   * WebAPI client that we wrap.
   *
   * @var \AlexaCRM\WebAPI\Client
   */
  protected Client $client;

  /**
   * To cache the results of API queries.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * The class logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructor for the "Connector" object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cacheBackend,
    LoggerChannelFactory $loggerFactory
  ) {
    $config = $config_factory->get('dynamics.settings');
    $this->logger = $loggerFactory->get('dynamics');
    $this->cacheBackend = $cacheBackend;

    // Unwrap values we need from configuration object.
    $this->instanceUri = $config->get('instance_uri');
    $this->applicationId = $config->get('application_id');
    $this->applicationSecret = $config->get('application_secret');
    $this->endpoint = $config->get('endpoint');
    $this->entityWhitelist = $config->get('entity_whitelist');

    // Client for talking to Dynamics.
    $this->client = ClientFactory::createOnlineClient(
      $this->instanceUri,
      $this->applicationId,
      $this->applicationSecret,
      ['logger' => $this->logger, 'cachePool' => $this->cacheBackend]
    );
  }

  /**
   * Get a partial entity definitions from Dynamics.
   *
   * @param string $logicalName
   *   Logical name of entity to get.
   *
   * @return \Drupal\dynamics\EntityDefinition
   *   Entity definition.
   *
   * @throws \Doctrine\Common\Annotations\AnnotationException
   */
  public function getEntityDefinitionByLogicalName(string $logicalName): ?EntityDefinition {
    $query = 'EntityDefinitions(LogicalName=\'' . $logicalName . '\')?$select=MetadataId';

    $body = $this->executeGetQuery($query);

    return $this->getEntityDefinitionByMetadataId($body->MetadataId);
  }

  /**
   * Return TRUE if a connection to Dynamics was successfully established.
   *
   * @return bool
   *   Return TRUE if a connection to Dynamics was successfully established.
   */
  public function testConnection() {
    try {
      $this->executeGetQuery('EntityDefinitions', FALSE);
    }
    catch (\Exception $e) {
      $this->logger->notice($e->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Execute a custom GET query and return the results.
   *
   * @param string $query
   *   Query to run.
   * @param bool $cached
   *   If FALSE, do not check for previously cached query results.
   *
   * @return mixed
   *   Decoded JSON.
   *
   * @todo sprinkle some error handling around.
   */
  protected function executeGetQuery(string $query, $cached = TRUE) {
    // Check cache.
    if ($cached && $result = $this->getCachedQuery($query)) {
      return $result;
    }

    // Unwrap the Guzzle HTTP client to use in our custom query.
    $httpClient = $this->client->getClient()->getHttpClient();

    $url = $this->endpoint . $query;

    $result = $httpClient->get($url);
    $result = json_decode((string) $result->getBody());

    $this->setCachedQuery($query, $result);

    return $result;
  }

  /**
   * Returns a cached query result.
   *
   * @return mixed
   *   The body of the previously cached query, else FALSE.
   */
  protected function getCachedQuery($query) {
    $queryId = md5($query);

    $result = $this->cacheBackend->get($queryId);
    return $result->data ?? FALSE;
  }

  /**
   * Sets a cached query result.
   *
   * @param string $query
   *   A query to cache.
   * @param mixed $data
   *   A cached data.
   */
  protected function setCachedQuery(string $query, $data) {
    $queryId = md5($query);

    $this
      ->cacheBackend
      ->set($queryId, $data, Cache::PERMANENT, [self::CACHE_TAG]);
  }

  /**
   * Get a partial entity definitions from Dynamics.
   *
   * @param string $metadataId
   *   Metadata ID of entity to get.
   *
   * @return \Drupal\dynamics\EntityDefinition
   *   Entity definition.
   *
   * @throws \Doctrine\Common\Annotations\AnnotationException
   */
  public function getEntityDefinitionByMetadataId(string $metadataId): EntityDefinition {
    $entityDefinition = new EntityDefinition();
    $entityDefinition->setMetadataId($metadataId);

    // @todo get entity logical name
    $this->fillEntityDefinition($entityDefinition);

    return $entityDefinition;
  }

  /**
   * Flesh out the partial entity definitions.
   *
   * @param \Drupal\dynamics\EntityDefinition $entityDefinition
   *   An entity definition.
   *
   * @throws \Doctrine\Common\Annotations\AnnotationException
   */
  protected function fillEntityDefinition(EntityDefinition $entityDefinition) {
    // Get the definitions of all the Process plugins.
    // phpcs:ignore
    $plugin_definitions = \Drupal::service('plugin.manager.dynamics.process')
      ->getDefinitions();

    // Build a list of @map_to annotations from the Process plugins.
    $valid_definitions = [];
    foreach ($plugin_definitions as $plugin_definition) {
      if (empty($plugin_definition['map_to'])) {
        throw new AnnotationException('@map_to annotation is required for Dynamics Process plugins');
      }
      $valid_definitions[] = $plugin_definition['map_to'];
    }
    $valid_definitions = array_unique($valid_definitions);

    $query = 'EntityDefinitions(' . $entityDefinition->getMetadataId() . ')?$expand=Attributes';
    $body = $this->executeGetQuery($query);

    $entity_attributes = $body->Attributes;

    foreach ($entity_attributes as $entity_attribute) {
      if (in_array($entity_attribute->AttributeType, $valid_definitions)) {
        $entityDefinition->setDefinition(
          $entity_attribute->MetadataId, [
            'MetadataId' => $entity_attribute->MetadataId,
            'AttributeType' => $entity_attribute->AttributeType,
            'LogicalName' => $entity_attribute->LogicalName,
            'IsValidForCreate' => $entity_attribute->IsValidForCreate,
            'IsValidForRead' => $entity_attribute->IsValidForRead,
            'IsValidForUpdate' => $entity_attribute->IsValidForUpdate,
          ]
        );
      }
    }
  }

  /**
   * Returns a simple list of entity names, useful for debugging.
   *
   * @return string[]
   *   List of EntityLogical names.
   */
  public function listEntityLogicalNames(): array {
    $query = 'EntityDefinitions?$select=LogicalName';
    $logicalNames = [];

    // Get query result.
    $body = $this->executeGetQuery($query);

    // We're only interested in the payload of the query.
    $entityDefinitions = $body->value;

    foreach ($entityDefinitions as $entityDefinition) {
      $logicalNames[] = $entityDefinition->LogicalName;
    }

    return $logicalNames;
  }

  /**
   * Get an array of partial entity definitions from Dynamics.
   *
   * Filters by the whitelist set in the module's Settings Form, so the rest of
   * the system should never see most of the Dynamics entities.
   *
   * @return array
   *   Array of Dynamics entity definitions.
   *
   * @throws \Doctrine\Common\Annotations\AnnotationException
   */
  public function getEntityDefinitions(): array {

    $query = 'EntityDefinitions?$select=LogicalName';

    // Get query result.
    $body = $this->executeGetQuery($query);

    // We're only interested in the payload of the query.
    $entityDefinitions = $body->value;

    // Map from an [] of stdClass to an [] of our EntityDefinition class.
    foreach ($entityDefinitions as &$row) {
      $row = $this->convertToEntityDefinition($row);
    }

    // We're only interested in the entity definitions that are in the
    // whitelist.
    // Note: '(LogicalName=\'' . $logicalName . '\')EntityDefinitions
    // ?$select=LogicalName
    // &$expand=Attributes'
    // would get us all the data we need in a single query, but would take
    // forever. We filter down to the entities we know we can handle, then get
    // the expanded structure.
    $entityDefinitions = $this->filterEntityDefinitions($entityDefinitions);

    // Fully populate our partial entity definitions and return them.
    foreach ($entityDefinitions as $entityDefinition) {
      $this->fillEntityDefinition($entityDefinition);
    }

    return $entityDefinitions;
  }

  /**
   * Convert from stdClass object to EntityDefinition object.
   *
   * // phpcs:ignore
   * @param \stdClass $in
   *   Entity definition derived from a Dynamics query.
   *
   * @return \Drupal\dynamics\EntityDefinition
   *   $in converted to an EntityDefinition.
   */
  protected function convertToEntityDefinition(\stdClass $in): EntityDefinition {
    $entityDefinition = new EntityDefinition();
    $entityDefinition->setMetadataId($in->MetadataId ?? '');
    $entityDefinition->setLogicalName($in->LogicalName ?? '');
    return $entityDefinition;
  }

  /**
   * Filtering for a List of entity definitions.
   *
   * @param array $entityDefinitions
   *   List of entity definitions.
   *
   * @return array
   *   List of entity definitions.
   */
  protected function filterEntityDefinitions(array $entityDefinitions): array {
    // If a whitelist hasn't been configured, return everything.
    if (empty($this->entityWhitelist)) {
      return $entityDefinitions;
    }

    foreach ($entityDefinitions as $key => $entityDefinition) {
      if (!in_array($entityDefinition->getLogicalName(), $this->entityWhitelist)) {
        unset($entityDefinitions[$key]);
      }
    }

    return array_values($entityDefinitions);
  }

  /**
   * Remove all data from cacheBackend.
   */
  public function invalidateCache() {
    Cache::invalidateTags([self::CACHE_TAG]);
  }

  /**
   * Create a Dynamics record from a queue submission.
   *
   * @param array $queueItem
   *   An queue item.
   *
   * @throws \AlexaCRM\WebAPI\OData\AuthenticationException
   * @throws \AlexaCRM\WebAPI\OrganizationException
   * @throws \AlexaCRM\WebAPI\ToolkitException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function create(array $queueItem): void {
    $entity = new Entity($queueItem['destination_entity']);

    foreach ($queueItem['data'] as $row) {
      $entity[$row['destination_field']] = $row['destination_value'];
    }
    $dynamicsId = $this->client->Create($entity);
    $preQueueEvent = new DynamicsEntityCreatedEvent(
      $queueItem['drupal_entity_type'],
      $queueItem['drupal_entity_id'],
      $queueItem['destination_entity'],
      $dynamicsId,
    );
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($preQueueEvent, DynamicsEntityCreatedEvent::EVENT_NAME);

  }

  /**
   * Update a Dynamics record from a queue submission.
   *
   * @param array $queueItem
   *
   * @return void
   *
   * @throws \AlexaCRM\WebAPI\OData\AuthenticationException
   * @throws \AlexaCRM\WebAPI\OrganizationException
   * @throws \AlexaCRM\WebAPI\ToolkitException
   */
  public function update(array $queueItem) {
    $record = new Entity($queueItem['destination_entity'], $queueItem['destination_id']);
    foreach ($queueItem['data'] as $row) {
      $record[$row['destination_field']] = $row['destination_value'];
    }
    $this->client->Update($record);
  }

}
