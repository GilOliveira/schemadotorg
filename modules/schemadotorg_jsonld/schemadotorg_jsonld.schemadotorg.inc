<?php

/**
 * @file
 * Schema.org Blueprints JSON-LD hook implementations for contributed modules.
 */

declare(strict_types = 1);

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Implements hook_schemadotorg_jsonld_schema_type_field_alter() for the datetime_range.module.
 *
 * Sets the endDate property for a single startDate property that is
 * using a daterange.
 */
function datetime_range_schemadotorg_jsonld_schema_type_field_alter(array &$data, FieldItemListInterface $items): void {
  $field_definition = $items->getFieldDefinition();

  // Only alter the Schema.org type data that have a daterange field.
  if ($field_definition->getType() !== 'daterange') {
    return;
  }

  // Only alter the Schema.org type data that have a single daterange field.
  $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
  if ($cardinality !== 1) {
    return;
  }

  // Get Schema.org mapping.
  $mapping = SchemaDotOrgMapping::loadByEntity($items->getEntity());

  // Check that the field is mapped to startDate.
  $schema_property = $mapping->getSchemaPropertyMapping($field_definition->getName());
  if ($schema_property !== 'startDate') {
    return;
  }

  // Check that the mapping's Schema.org type has an endDate property.
  /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager */
  $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
  $schema_type = $mapping->getSchemaType();

  // Set the endDate property.
  if ($schema_type_manager->hasProperty($schema_type, 'endDate')
    && empty($data['endDate'])
    && !empty($items->end_value)) {
    $data['endDate'] = $items->end_value;
  }

  // @todo Set the duration property.
}

/**
 * Implements hook_schemadotorg_jsonld_schema_property_alter() for the address.module.
 */
function address_schemadotorg_jsonld_schema_property_alter(mixed &$value, FieldItemInterface $item): void {
  $field_type = $item->getFieldDefinition()->getType();
  if ($field_type !== 'address') {
    return;
  }

  $mapping = [
    'country_code' => 'addressCountry',
    'administrative_area' => 'addressRegion',
    'locality' => 'addressLocality',
    'dependent_locality' => 'addressLocality',
    'postal_code' => 'postalCode',
    'sorting_code' => 'postOfficeBoxNumber',
    'address_line1' => 'streetAddress',
    'address_line2' => 'streetAddress',
  ];

  // Map organization and full name to Schema.org name and
  // alternateName properties.
  $values = $item->getValue();
  $values['organization'] = trim($values['organization']);
  $values['name'] = implode(' ', array_filter([
    trim($values['given_name']),
    trim($values['additional_name']),
    trim($values['family_name']),
  ]));
  if ($values['organization']) {
    $mapping['organization'] = 'name';
    $mapping['name'] = 'alternateName';
  }
  else {
    $mapping['name'] = 'name';
  }

  $data = ['@type' => 'PostalAddress'];
  foreach ($mapping as $source => $destination) {
    if ($destination && !empty($values[$source])) {
      if (isset($data[$destination])) {
        $data[$destination] .= ', ' . $values[$source];
      }
      else {
        $data[$destination] = $values[$source];
      }
    }
  }

  /** @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $jsonld_manager */
  $jsonld_manager = \Drupal::service('schemadotorg_jsonld.manager');
  $value = $jsonld_manager->sortProperties($data);
}