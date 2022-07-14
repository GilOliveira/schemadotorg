<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org mapping manager interface.
 */
interface SchemaDotOrgMappingManagerInterface {

  /**
   * Gets ignored Schema.org properties.
   *
   * @return array
   *   Ignored Schema.org properties.
   */
  public function getIgnoredProperties();

  /**
   * Get Schema.org mapping default values.
   *
   * @param string $entity_type_id
   *   The Schema.org type.
   * @param string $bundle
   *   The entity type id.
   * @param string $schema_type
   *   The bundle.
   *
   * @return array
   *   Schema.org mapping default values.
   */
  public function getMappingDefaults($entity_type_id, $bundle, $schema_type);

  /**
   * Save a Schema.org mapping and create associate entity type and fields.
   *
   * @param string $entity_type_id
   *   The Schema.org type.
   * @param string $schema_type
   *   The entity type ID.
   * @param array $values
   *   The entity, subtype, and property values.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   *   A Schema.org mapping.
   */
  public function saveMapping($entity_type_id, $schema_type, array $values);

  /**
   * Validate create Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $schema_type
   *   The Schema.org type.
   */
  public function createTypeValidate($entity_type_id, $schema_type);

  /**
   * Create Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return array
   *   An array of all errors, keyed by the name of the form element.
   */
  public function createType($entity_type_id, $schema_type);

  /**
   * Validate delete Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $schema_type
   *   The Schema.org type.
   */
  public function deleteTypeValidate($entity_type_id, $schema_type);

  /**
   * Delete Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $options
   *   (optional) An array of options.
   */
  public function deleteType($entity_type_id, $schema_type, array $options = []);

}
