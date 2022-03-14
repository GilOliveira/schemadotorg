<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for 'schemadotorg_mapping' storage.
 */
interface SchemaDotOrgMappingStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Determine if an entity type and bundle are mapped to Schema.org.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return bool
   *   TRUE if an entity type and bundle are mapped to Schema.org.
   */
  public function isBundleMapped($entity_type_id, $bundle);

  /**
   * Determine if Schema.org type is mapped to an entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $type
   *   The Schema.org type.
   *
   * @return bool
   *   TRUE if Schema.org type is mapped to an entity.
   */
  public function isSchemaTypeMapped($entity_type_id, $type);

  /**
   * Load by target entity id and Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $type
   *   The Schema.org type.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   A Schema.org mapping entity.
   */
  public function loadBySchemaType($entity_type_id, $type);

}
