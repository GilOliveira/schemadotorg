<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Schema.org JSON-LD manager interface.
 */
interface SchemaDotOrgJsonLdManagerInterface {

  /**
   * Get a Schema.org property's value for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return array|mixed|null
   *   A Schema.org property's value for a field item.
   */
  public function getPropertyValue(FieldItemInterface $item);

}
