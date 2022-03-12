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
   * Get an entity type's base fields names.
   *
   * @param string $entity_type_id
   *   An entity type.
   *
   * @return array|string[]
   *   An entity type's base fields names.
   */
  public function getBaseFieldNames($entity_type_id);

  /**
   * Get field types for Schema.org property.
   *
   * @param string $property
   *   A Schema.org property.
   *
   * @return array
   *   Field types for Schema.org property.
   */
  public function getSchemaPropertyFieldTypes($property);

}
