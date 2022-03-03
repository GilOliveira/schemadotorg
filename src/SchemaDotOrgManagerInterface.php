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
   * Get Schema.org type.
   *
   * @param string $type
   *   A Schema.org type.
   *
   * @return array|false
   *   An associative array containing Schema.org type definition,
   *   or FALSE if there is no type found.
   */
  public function getType($type);

  /**
   * Get Schema.org property.
   *
   * @param string $property
   *   A Schema.org property.
   *
   * @return array|false
   *   An associative array containing Schema.org property definition,
   *   or FALSE if there is no property found.
   */
  public function getProperty($property);

  /**
   * Get all child Schema.org types below a specified type.
   *
   * @param string $type
   *   A Schema.org type.
   *
   * @return array
   *   An associative array of Schema.org types keyed by type.
   */
  public function getTypeChildren($type);

  /**
   * Get all Schema.org types below a specified type.
   *
   * @param string $type
   *   A Schema.org type.
   * @param array $fields
   *   An array of Schema.org type fields.
   * @param array $ignored_types
   *   An array of ignored Schema.org type ids.
   *
   * @return array
   *   An associative array of Schema.org types keyed by type.
   */
  public function getAllTypeChildren($type, array $fields = [], array $ignored_types = []);

}
