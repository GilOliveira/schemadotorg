<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Schema.org JSON-LD manager interface.
 */
interface SchemaDotOrgJsonLdManagerInterface {

  /**
   * Sort Schema.org properties in specified order and then alphabetically.
   *
   * @param array $properties
   *   An associative array of Schema.org properties.
   *
   * @return array
   *   The Schema.org propertiesin specified order and then alphabetically.
   */
  public function sortProperties(array $properties);

  /**
   * Get a Schema.org property's value for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return array|mixed|null
   *   A Schema.org property's value for a field item.
   */
  public function getSchemaPropertyValue(FieldItemInterface $item);

}
