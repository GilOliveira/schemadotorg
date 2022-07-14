<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity type builder interface.
 */
interface SchemaDotOrgEntityTypeBuilderInterface {

  /**
   * Add bundle entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $values
   *   The entity bundle values.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The bundle entity type.
   */
  public function addBundleEntity($entity_type_id, $schema_type, array $values);

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
   * Set entity display field weights for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $properties
   *   The Schema.org properties to be weighted.
   */
  public function setEntityDisplayFieldWeights($entity_type_id, $bundle, array $properties);

  /**
   * Set entity display field groups for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $properties
   *   The Schema.org properties to be added to field groups.
   */
  public function setEntityDisplayFieldGroups($entity_type_id, $bundle, $schema_type, array $properties);

}
