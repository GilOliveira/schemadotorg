<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Schema.org JSON-LD manager interface.
 */
interface SchemaDotOrgJsonLdManagerInterface {

  /**
   * Alter a Schema.org property field value.
   *
   * @param mixed &$value
   *   The property's current value extracted from the field item.
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   */
  public function alterPropertyValue(&$value, FieldItemInterface $item);

}
