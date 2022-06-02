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
 * @} End of "addtogroup hooks".
 */
