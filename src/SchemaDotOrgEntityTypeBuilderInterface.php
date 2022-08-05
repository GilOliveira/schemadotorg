<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity type builder interface.
 */
interface SchemaDotOrgEntityTypeBuilderInterface {

  /**
   * Add entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $values
   *   The entity bundle values.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The bundle entity type.
   */
  public function addEntityBundle($entity_type_id, $schema_type, array $values);

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

}
