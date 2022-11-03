<?php

/**
 * @file
 * Schema.org hook implementations for contributed modules.
 */

declare(strict_types = 1);

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Locale\CountryManager;

/* ************************************************************************** */
// Allowed values.
/* ************************************************************************** */

/**
 * HOOK_allowed_values_SCHEMA_TYPE()
 */
function schemadotorg_allowed_values_country(): array {
  return CountryManager::getStandardList();
}

/**
 * HOOK_allowed_values_SCHEMA_TYPE()
 */
function schemadotorg_allowed_values_language(): array {
  $languages = LanguageManager::getStandardLanguageList();
  unset($languages['en-x-simple']);
  $allowed_values = [];
  foreach ($languages as $language) {
    $allowed_values[$language[0]] = $language[0];
  }
  return $allowed_values;
}

/* ************************************************************************** */
// Duration field.
/* ************************************************************************** */

/**
 * Implements hook_schemadotorg_property_field_alter() for the duration_field.module.
 */
function duration_field_schemadotorg_property_field_alter(
  string $schema_type,
  string $schema_property,
  array &$field_storage_values,
  array &$field_values,
  ?string &$widget_id,
  array &$widget_settings,
  ?string &$formatter_id,
  array &$formatter_settings
): void {
  // Make sure the field type is set to 'duration'.
  if ($field_storage_values['type'] !== 'duration') {
    return;
  }

  // Set duration granularity to hours and minutes.
  $field_values['settings'] = ['granularity' => 'h:i'];
}

/**
 * Implements hook_schemadotorg_jsonld_schema_property_alter() for the duration_field.module.
 */
function duration_field_schemadotorg_jsonld_schema_property_alter(mixed &$value, FieldItemInterface $item): void {
  $field_type = $item->getFieldDefinition()->getType();
  if ($field_type !== 'duration') {
    return;
  }

  $value = $item->duration;
}
