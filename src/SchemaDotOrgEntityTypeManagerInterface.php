<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity type manager interface.
 */
interface SchemaDotOrgEntityTypeManagerInterface {

  /**
   * Get entity types that implement Schema.org.
   *
   * @return array
   *   Entity types that implement Schema.org.
   */
  public function getEntityTypes();

  /**
   * Get default Schema.org type for an entity type and bundle.
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
   * Get an entity type's base fields names.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array|string[]
   *   An entity type's base fields names.
   */
  public function getBaseFieldNames($entity_type_id);

  /**
   * Get common Schema.org types for specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   Common Schema.org types for specific entity type.
   */
  public function getCommonSchemaTypes($entity_type_id);

  /**
   * Get field types for Schema.org property.
   *
   * @param string $property
   *   The Schema.org property.
   *
   * @return array
   *   Field types for Schema.org property.
   */
  public function getSchemaPropertyFieldTypes($property);

}
