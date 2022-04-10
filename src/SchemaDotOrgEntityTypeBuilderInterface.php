<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity type builder interface.
 */
interface SchemaDotOrgEntityTypeBuilderInterface {

  /**
   * Get Schema.org type vocabulary id.
   *
   * @param string $type
   *   Schema.org type.
   *
   * @return string
   *   Schema.org type vocabulary id.
   */
  public function getTypeVocabularyId($type);

  /**
   * Create type vocabularies.
   *
   * @param string $type
   *   The Schema.org type.
   */
  public function createTypeVocabulary($type);

  /**
   * Add a field to an entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $field
   *   The field to be added to the entity.
   */
  public function addFieldToEntity($entity_type_id, $bundle, array $field);

  /**
   * Set entity display field groups for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $properties
   *   The Schema.org properties to be added to field groups.
   */
  public function setEntityDisplayFieldGroups($entity_type_id, $bundle, array $properties);

}
