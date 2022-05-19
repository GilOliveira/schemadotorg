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
  // Get entity information.
  $entity = $item->getEntity();

  // Get field information.
  $field_storage_definition = $item->getFieldDefinition()->getFieldStorageDefinition();
  $field_name = $item->getName();
  $field_type = $field_storage_definition->getType();

  // Get main property information.
  $main_property_name = $field_storage_definition->getMainPropertyName();
  $main_property_definition = $field_storage_definition->getPropertyDefinition($main_property_name);
  $main_property_data_type = $main_property_definition->getDataType();

  // Get Schema.org mapping.
  /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
  $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');
  $mapping = $mapping_storage->loadByEntity($entity);
  $schema_property = $mapping->getSchemaPropertyMapping($field_name);

  $config = \Drupal::config('schemadotorg_jsonld.settings');

  // Field type.
  switch ($field_type) {
    case 'address':
      $values = $item->getValue();
      $address_mapping = [
        'country_code' => 'addressCountry',
        'administrative_area' => 'addressRegion',
        'locality' => 'addressLocality',
        'dependent_locality' => 'addressLocality',
        'postal_code' => 'postalCode',
        'sorting_code' => 'postOfficeBoxNumber',
        'address_line1' => 'streetAddress',
        'address_line2' => 'streetAddress',
        'organization' => 'name',
        'given_name' => 'name',
        'additional_name' => 'name',
        'family_name' => 'name',
      ];
      $data = [];
      foreach ($address_mapping as $address_field_property => $postal_address_property) {
        if ($postal_address_property && !empty($values[$address_field_property])) {
          if (isset($data[$postal_address_property])) {
            $data[$postal_address_property] .= ' ' . $values[$address_field_property];
          }
          else {
            $data[$postal_address_property] = $values[$address_field_property];
          }
        }
      }
      $value = ['@type' => 'PostalAddress'] + $data;
      return;

    case 'link':
      $value = $item->uri;
      return;

    case 'text_long':
    case 'text_with_summary':
      $value = check_markup($item->value, $item->format);
      return;

    case 'image':
    case 'file':
      $file_uri = $item->entity->getFileUri();
      $style = $config->get('property_image_styles.' . $schema_property);
      if ($field_type === 'image' && $style) {
        $image_style_storage = \Drupal::entityTypeManager()->getStorage('image_style');
        $image_style = $image_style_storage->load($style);
        if ($image_style) {
          $value = $image_style->buildUrl($file_uri);
          return;
        }
      }
      $value = \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri);
      return;
  }

  // Main property data type.
  if (!is_array($value)) {
    switch ($main_property_data_type) {
      case 'timestamp':
        $value = \Drupal::service('date.formatter')->format($value, 'custom', 'Y-m-d H:i:s P');
        return;
    }
  }

}
