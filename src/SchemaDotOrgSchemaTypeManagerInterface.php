<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org schema data type manager interface.
 */
interface SchemaDotOrgSchemaTypeManagerInterface {

  /**
   * Schema.org URI.
   *
   * @var string
   */
  const URI = 'https://schema.org/';

  /**
   * Gets Schema.org type or property URI.
   *
   * @param string $id
   *   A Schema.org type or property.
   *
   * @return string
   *   Schema.org type or property URI.
   */
  public function getUri($id);

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
   * Determine if ID is a Schema.org type or property.
   *
   * @param string $id
   *   A Schema.org ID.
   *
   * @return bool
   *   TRUE if ID is a Schema.org type or property.
   */
  public function isItem($id);

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
   * Determine if ID is a Schema.org data type.
   *
   * @param string $id
   *   A Schema.org ID.
   *
   * @return bool
   *   TRUE if ID is a Schema.org data type.
   */
  public function isDataType($id);

  /**
   * Determine if ID is a Schema.org enumeration type.
   *
   * @param string $id
   *   A Schema.org ID.
   *
   * @return bool
   *   TRUE if ID is a Schema.org enumeration type.
   */
  public function isEnumerationType($id);

  /**
   * Determine if ID is a Schema.org enumeration value.
   *
   * @param string $id
   *   A Schema.org ID.
   *
   * @return bool
   *   TRUE if ID is a Schema.org enumeration value.
   */
  public function isEnumerationValue($id);

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
   * Parse Schema.org type or property IDs from a comma delimited list of URLs.
   *
   * @param string $text
   *   A comma delimited list of Schema.org URLs.
   *
   * @return string[]
   *   An array of Schema.org types.
   */
  public function parseIds($text);

  /**
   * Gets Schema.org type or property item.
   *
   * @param string $table
   *   A Schema.org table.
   * @param string $id
   *   A Schema.org type or property ID.
   * @param array $fields
   *   Fields to returned.
   *
   * @return array
   *   An associative array containing Schema.org type or property item.
   *   or FALSE if there is no type found.
   */
  public function getItem($table, $id, array $fields = []);

  /**
   * Gets Schema.org type.
   *
   * @param string $type
   *   The Schema.org type.
   *
   * @return array|false
   *   An associative array containing Schema.org type definition,
   *   or FALSE if there is no type found.
   */
  public function getType($type);

  /**
   * Gets Schema.org property.
   *
   * @param string $property
   *   The Schema.org property.
   *
   * @return array|false
   *   An associative array containing Schema.org property definition,
   *   or FALSE if there is no property found.
   */
  public function getProperty($property);

  /**
   * Gets a Schema.org type's properties.
   *
   * @param string $type
   *   The Schema.org type.
   * @param array $fields
   *   An array of Schema.org property fields.
   *
   * @return array
   *   An associative array of a Schema.org type's properties.
   */
  public function getTypeProperties($type, array $fields = []);

  /**
   * Gets all child Schema.org types below a specified type.
   *
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   An associative array of Schema.org types keyed by type.
   */
  public function getTypeChildren($type);

  /**
   * Gets Schema.org subtypes.
   *
   * @param string $type
   *   A Schema.org type.
   *
   * @return array
   *   Array containing Schema.org subtypes.
   */
  public function getSubtypes($type);

  /**
   * Gets Schema.org enumerations.
   *
   * @param string $type
   *   A Schema.org type.
   *
   * @return array
   *   Array containing Schema.org enumerations.
   */
  public function getEnumerations($type);

  /**
   * Gets Schema.org data types.
   *
   * @return array|string[]
   *   An array of data types.
   */
  public function getDataTypes();

  /**
   * Gets all Schema.org subtypes below specified Schema.org types.
   *
   * @param array $types
   *   An array of Schema.org types.
   *
   * @return array
   *   An array of Schema.org subtypes which includes the specified
   *   Schema.org types
   */
  public function getAllSubTypes(array $types);

  /**
   * Gets all Schema.org types below a specified type.
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

  /**
   * Gets Schema.org type hierarchical tree.
   *
   * @param string $type
   *   A Schema.org type.
   * @param array $ignored_types
   *   An array of ignored Schema.org types.
   *
   * @return array
   *   An associative nested array containing Schema.org type hierarchical tree.
   */
  public function getTypeTree($type, array $ignored_types = []);

  /**
   * Gets Schema.org type breadcrumbs.
   *
   * @param string $type
   *   A Schema.org type.
   *
   * @return array
   *   An associative nested array containing Schema.org type breadcrumbs.
   */
  public function getTypeBreadcrumbs($type);

}
