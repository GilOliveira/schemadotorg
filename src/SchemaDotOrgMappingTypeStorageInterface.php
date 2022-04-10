<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for 'schemadotorg_mapping_type' storage.
 */
interface SchemaDotOrgMappingTypeStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Gets entity types that implement Schema.org.
   *
   * @return array
   *   Entity types that implement Schema.org.
   */
  public function getEntityTypes();

  /**
   * Gets default bundle for an entity type and Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   The default bundles for an entity type and Schema.org type.
   */
  public function getDefaultSchemaTypeBundles($entity_type_id, $type);

  /**
   * Gets default Schema.org type for an entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string|null
   *   The default Schema.org type for an entity type and bundle.
   */
  public function getDefaultSchemaType($entity_type_id, $bundle);

  /**
   * Gets default field groups for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An associative array containing default field groups for a
   *   specific entity type.
   */
  public function getDefaultFieldGroups($entity_type_id);

  /**
   * Gets common Schema.org types for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An associative array containing common Schema.org types for
   *   a specific entity type.
   */
  public function getRecommendedSchemaTypes($entity_type_id);

  /**
   * Gets an entity type's base field mappings.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An entity type's base field mappings.
   */
  public function getBaseFieldMappings($entity_type_id);

  /**
   * Gets an entity type's base fields names.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An entity type's base fields names.
   */
  public function getBaseFieldNames($entity_type_id);

  /**
   * Get entity type bundles. (i.e node)
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   Entity type bundles.
   */
  public function getEntityTypeBundles();

  /**
   * Get entity type bundle definitions. (i.e node_type)
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface[]
   *   Entity type bundle definitions.
   */
  public function getEntityTypeBundleDefinitions();

}
