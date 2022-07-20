<?php

namespace Drupal\schemadotorg_mapping_set;

/**
 * Schema.org mapping set manager interface.
 */
interface SchemaDotOrgMappingSetManagerInterface {

  /**
   * Setup the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   *
   * @return array
   *   An array of messages.
   */
  public function setup($name);

  /**
   * Teardown the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   *
   * @return array
   *   An array of messages.
   */
  public function teardown($name);

  /**
   * Generate the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   */
  public function generate($name);

  /**
   * Kill the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   */
  public function kill($name);

  /**
   * Get Schema.org types from mapping set name.
   *
   * @param string $name
   *   Schema.org mapping set name.
   * @param bool $required
   *   Include required types.
   *
   * @return array
   *   Schema.org types.
   */
  public function getTypes($name, $required = FALSE);

  /**
   * Determine if a Schema.org mapping set is already setup.
   *
   * @param string $name
   *   A Schema.org mapping set name.
   *
   * @return bool
   *   If a Schema.org mapping set is already setup.
   */
  public function isSetup($name);

  /**
   * Determine if a mapping set type is valid.
   *
   * @param string $type
   *   A mapping set type (i.e. entity_type_id:SchemaType).
   *
   * @return bool
   *   TRUE if a mapping set type is valid.
   */
  public function isValidType($type);

}
