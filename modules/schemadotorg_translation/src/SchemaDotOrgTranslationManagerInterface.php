<?php

namespace Drupal\schemadotorg_translation;

use Drupal\field\FieldConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org translate manager interface.
 */
interface SchemaDotOrgTranslationManagerInterface {

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
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field.
   */
  public function enableMappingField(FieldConfigInterface $field_config);

}
