<?php

namespace Drupal\schemadotorg_ui;

/**
 * Schema.org UI API interface.
 */
interface SchemaDotOrgUiApiInterface {

  /**
   * Validate create Schema.org type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $schema_type
   *   The Schema.org type.
   */
  public function createTypeValidate($entity_type, $schema_type);

  /**
   * Create Schema.org type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $options
   *   (optional) An array of options.
   */
  public function createType($entity_type, $schema_type, array $options = []);

  /**
   * Validate delete Schema.org type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $schema_type
   *   The Schema.org type.
   */
  public function deleteTypeValidate($entity_type, $schema_type);

  /**
   * Delete Schema.org type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $options
   *   (optional) An array of options.
   */
  public function deleteType($entity_type, $schema_type, array $options = []);

}
