<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity type builder interface.
 */
interface SchemaDotOrgEntityTypeBuilderInterface {

  /**
   * Create type vocabularies.
   *
   * @param string $type
   *   The Schema.org type.
   */
  public function createTypeVocabulary($type);

  /**
   * {@inheritdoc}
   */
  public function addFieldToEntity($entity_type_id, $bundle, array $field);

}
