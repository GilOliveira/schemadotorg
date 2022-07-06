<?php

namespace Drupal\schemadotorg_translate;

use Drupal\field\FieldConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org translate manager interface.
 */
interface SchemaDotOrgTranslateManagerInterface {

  /**
   * Enable translation for a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function enableMapping(SchemaDotOrgMappingInterface $mapping);

  /**
   * Enable translation for a Schema.org mapping field.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field.
   */
  public function enableField(FieldConfigInterface $field);

}
