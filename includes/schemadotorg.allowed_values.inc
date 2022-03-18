<?php

/**
 * @file
 * Schema.org type allowed values.
 */

use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Language\LanguageManager;


/**
 * HOOK_allowed_values_SCHEMA_TYPE()
 */
function schemadotorg_allowed_values_country() {
  return CountryManager::getStandardList();
}

/**
 * HOOK_allowed_values_SCHEMA_TYPE()
 */
function schemadotorg_allowed_values_language() {
  $languages = LanguageManager::getStandardLanguageList();
  unset($languages['en-x-simple']);
  $allowed_values = [];
  foreach ($languages as $language) {
    $allowed_values[$language[0]] = $language[0];
  }
  return $allowed_values;
}
