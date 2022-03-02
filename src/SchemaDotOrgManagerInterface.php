<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org manager interface.
 */
interface SchemaDotOrgManagerInterface {

  /**
   * Determine if ID is in a valid Schema.org table.
   *
   * @param string $table
   *   A Schema.org table.
   * @param string $id
   *   A Schema.org ID.
   *
   * @return bool
   *   TRUE if ID is a Schema.org type.
   */
  public function isId($table, $id);

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

  /**
   * Parse Schema.org types or properties from a comma delimited list of URLs.
   *
   * @param string $text
   *   A comma delimited list of Schema.org URLs.
   *
   * @return string[]
   *   An array of Schema.org types.
   */
  public function parseItems($text);

  /**
   * Get all Schema.org types below a specified type.
   *
   * @param string $type
   *   A Schema.org type.
   * @param array $fields
   *   An array of Schema.org type fields.
   *
   * @return array
   *   An associative array of Schema.org types keyed by type.
   */
  public function getTypeChildren($type, $fields = ['label', 'sub_types']);


}
