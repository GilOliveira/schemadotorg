<?php

namespace Drupal\schemadotorg_taxonomy;

/**
 * Schema.org taxonomy vocabulary property manager interface.
 */
interface SchemaDotOrgTaxonomyPropertyVocabularyManagerInterface {

  /**
   * Implements hook_schemadotorg_property_field_type_alter().
   */
  public function propertyFieldTypeAlter(array &$field_types, $schema_type, $schema_property);

  /**
   * Implements hook_schemadotorg_property_field_alter().
   */
  public function propertyFieldAlter(
    $schema_type,
    $schema_property,
    array &$field_storage_values,
    array &$field_values,
    &$widget_id,
    array &$widget_settings,
    &$formatter_id,
    array &$formatter_settings
  );

}
