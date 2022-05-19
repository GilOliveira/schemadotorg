<?php

/**
 * @file
 * Hooks related to Schema.org Blueprints JSON-LD module.
 */

use Drupal\Core\Field\FieldItemInterface;

/**
 * Implements hook_schemadotorg_jsonld_schema_property_alter().
 */
function schemadotorg_jsonld_schemadotorg_jsonld_schema_property_alter(&$value, FieldItemInterface $item) {
  /** @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $manager */
  $manager = \Drupal::service('schemadotorg_jsonld.manager');
  $manager->alterPropertyValue($value, $item);
}
