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
   * Get field types as options.
   *
   * @return array
   *   An associative array of field types as options.
   */
  public function getFieldTypesAsOptions();

  /**
   * Get a Schema.org property's field type options.
   *
   * @param string $property
   *   A Schema.org property.
   *
   * @return array
   *   An associative array of Schema.org property's field type as options.
   */
  public function getSchemaPropertyFieldTypesAsOptions($property);
}
