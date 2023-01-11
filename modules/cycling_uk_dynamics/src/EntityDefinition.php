<?php

namespace Drupal\cycling_uk_dynamics;

/**
 * Simple container that can be filled with the definition of a Dynamics entity.
 */
class EntityDefinition implements \IteratorAggregate {

  /**
   * Name of entity in Dynamics.
   *
   * @var string
   */
  protected string $logicalName;

  /**
   * GUID of entity in Dynamics.
   *
   * @var string
   */
  protected string $metadataId;

  /**
   * Array of field definitions associated with this entity.
   *
   * @var array
   */
  protected array $definitions;

  /**
   * Constructor.
   *
   * @param string $logicalName
   *   A logical Name.
   * @param string $metadataId
   *   A logical Name.
   */
  public function __construct($logicalName = '', $metadataId = '') {
    if (!empty($logicalName)) {
      $this->setLogicalName($logicalName);
    }

    if (!empty($metadataId)) {
      $this->setMetadataId($metadataId);
    }

    $this->definitions = [];
  }

  /**
   * Getter.
   *
   * @return string
   *   A logical Name.
   */
  public function getLogicalName(): string {
    return $this->logicalName;
  }

  /**
   * Setter.
   *
   * @param string $logicalName
   *   A logical Name.
   */
  public function setLogicalName(string $logicalName) {
    $this->logicalName = $logicalName;
  }

  /**
   * Getter.
   *
   * @return string
   *   A Metadata ID.
   */
  public function getMetadataId(): string {
    return $this->metadataId;
  }

  /**
   * Setter.
   *
   * @param string $metadataId
   *   A Metadata ID.
   */
  public function setMetadataId(string $metadataId) {
    $this->metadataId = $metadataId;
  }

  /**
   * Setter.
   *
   * @param string $metadataId
   *   A Metadata ID.
   * @param mixed $definition
   *   A list of definitions.
   */
  public function setDefinition(string $metadataId, $definition) {
    $this->definitions[$metadataId] = $definition;
  }

  /**
   * If you loop over this class, you get the definitions array.
   *
   * @return \Traversable
   *   A list of definitions.
   */
  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->definitions);
  }

}
