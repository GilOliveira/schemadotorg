<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for 'schemadotorg_mapping' storage.
 */
interface SchemaDotOrgMappingStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Determine if an entity is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity is mapped to a Schema.org type.
   */
  public function isEntityMapped(EntityInterface $entity);

  /**
   * Determine if an entity type and bundle are mapped to a Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return bool
   *   TRUE if an entity type and bundle are mapped to a Schema.org type.
   */
  public function isBundleMapped($entity_type_id, $bundle);

  /**
   * Gets the Schema.org property name for an entity field mapping.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The Schema.org property name for an entity field mapping.
   */
  public function getSchemaPropertyName($entity_type_id, $bundle, $field_name);

  /**
   * Get a Schema.org property's target bundles.
   *
   * @param string $target_type
   *   The target entity type ID.
   * @param string $schema_property
   *   The Schema.org property.
   * @param string|null $schema_type
   *   The Schema.org type.
   *
   * @return array
   *   A Schema.org property's target bundles.
   */
  public function getSchemaPropertyTargetBundles($target_type, $schema_property, $schema_type = NULL);

  /**
   * Gets the Schema.org range includes target bundles.
   *
   * @param string $target_type
   *   The target entity type ID.
   * @param array $range_includes
   *   An array of Schema.org types.
   *
   * @return array
   *   The Schema.org range includes target bundles.
   */
  public function getRangeIncludesTargetBundles($target_type, array $range_includes);

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

  /**
   * Load by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   A Schema.org mapping entity.
   */
  public function loadByEntity(EntityInterface $entity);

  /**
   * Get the Schema.org subtype for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The Schema.org subtype for an entity.
   */
  public function getSubtype(EntityInterface $entity);

}
