<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity type manager interface.
 */
interface SchemaDotOrgEntityTypeManagerInterface {

  /**
   * Gets field types for Schema.org property.
   *
   * @param string $property
   *   The Schema.org property.
   *
   * @return array
   *   Field types for Schema.org property.
   */
  public function getSchemaPropertyFieldTypes($property);

}
