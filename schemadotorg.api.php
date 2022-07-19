<?php

/**
 * @file
 * Hooks related to Schema.org Blueprints module.
 */

// phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the field types for Schema.org property.
 *
 * @param array $field_types
 *   An array of field types.
 * @param string $type
 *   The Schema.org type.
 * @param string $property
 *   The Schema.org property.
 */
function hook_schemadotorg_property_field_type_alter(array &$field_types, $type, $property) {
  // @todo Provide an example.
}

/**
 * Prepare a property's field data before the Schema.org mapping form.
 *
 * @param string $type
 *   The Schema.org type.
 * @param string $property
 *   The Schema.org property.
 * @param array $default_field
 *   The default values used in the Schema.org mapping form.
 */
function hook_schemadotorg_property_field_prepare($type, $property, array &$default_field) {
  // @todo Provide an example.
}

/**
 * Alter bundle entity type before it is created.
 *
 * @param string $schema_type
 *   The Schema.org type.
 * @param string $entity_type_id
 *   The entity type id.
 * @param array &$values
 *   The bundle entity type values.
 */
function hook_schemadotorg_bundle_entity_alter($schema_type, $entity_type_id, &$values) {
  // @todo Provide an example.
}

/**
 * Alter field storage and field values before they are created.
 *
 * @param string $type
 *   The Schema.org type.
 * @param string $property
 *   The Schema.org property.
 * @param array $field_storage_values
 *   Field storage config values.
 * @param array $field_values
 *   Field config values.
 * @param string $widget_id
 *   The plugin ID of the widget.
 * @param array $widget_settings
 *   An array of widget settings.
 * @param string|null $formatter_id
 *   The plugin ID of the formatter.
 * @param array $formatter_settings
 *   An array of formatter settings.
 */
function hook_schemadotorg_property_field_alter(
  $type,
  $property,
  array &$field_storage_values,
  array &$field_values,
  &$widget_id,
  array &$widget_settings,
  &$formatter_id,
  array &$formatter_settings
) {
  // @todo Provide an example.
}

/**
 * Alter Schema.org mapping entity default values.
 *
 * @param string $entity_type_id
 *   The Schema.org type.
 * @param string $bundle
 *   The entity type.
 * @param string $schema_type
 *   The bundle.
 * @param array $defaults
 *   Schema.org mapping entity default values.
 */
function hook_schemadotorg_mapping_defaults($entity_type_id, $bundle, $schema_type, array &$defaults) {

}

/**
 * Save a Schema.org mapping entity values.
 *
 * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
 *   The Schema.org mapping.
 * @param array $values
 *   The Schema.org mapping entity values.
 */
function hook_schemadotorg_mapping_save(\Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping, array &$values) {

}

/**
 * @} End of "addtogroup hooks".
 */
