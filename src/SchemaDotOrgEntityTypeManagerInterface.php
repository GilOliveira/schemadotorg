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
   * Get default bundle for an entity type and Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $type
   *   The Schema.org type.
   *
   * @return string|null
   *   The default bundle for an entity type and Schema.org type.
   */
  public function getDefaultSchemaTypeBundle($entity_type_id, $type);

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
   * Get common Schema.org types for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An associative array containing common Schema.org types for
   *   a specific entity type.
   */
  public function getCommonSchemaTypes($entity_type_id);

  /**
   * Get an entity type's base fields names.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An entity type's base fields names.
   */
  public function getBaseFieldNames($entity_type_id);

  /**
   * Get default Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   Default Schema.org properties.
   */
  public function getSchemaPropertyDefaults($entity_type_id);

  /**
   * Get default Schema.org unlimited properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   Default Schema.org unlimited properties.
   */
  public function getSchemaPropertyUnlimited($entity_type_id);

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
