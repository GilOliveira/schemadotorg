<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org manager interface.
 */
interface SchemaDotOrgManagerInterface {


  /**
   * Determine if ID is a Schema.org type.
   *
   * @param string $id
   *   A Schema.org ID.
   *
   * @return bool
   *   TRUE if ID is a Schema.org type.
   */
  public function isType($id);

  /**
   * Determine if ID is a Schema.org property.
   *
   * @param string $id
   *   A Schema.org ID.
   *
   * @return bool
   *   TRUE if ID is a Schema.org property.
   */
  public function isProperty($id);

  /**
   * Get Schema.org field definitions for types and properties.
   *
   * @param string $name
   *   Table name (types or properties).
   *
   * @return array
   *   Field definitions for types and properties.
   */
  public function getFieldDefinitions($name);

}
