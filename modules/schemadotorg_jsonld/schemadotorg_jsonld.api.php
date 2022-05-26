<?php

/**
 * @file
 * Hooks related to Schema.org Blueprints JSON-LD module.
 */

// phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the Schema.org JSON-LD data for an entity.
 *
 * Besides, altering an existing Schema.org mapping's JSON-LD data, modules can
 * define custom JSON-LD data for any entity type.
 */
function hook_schemadotorg_jsonld_entity_alter(array &$data, \Drupal\Core\Entity\EntityInterface $entity) {
  // Get entity information.
  $entity_type_id = $entity->getEntityTypeId();
  $bundle = $entity->bundle();

  // Get Schema.org mapping.
  /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
  $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');
  $mapping = $mapping_storage->loadByEntity($entity);
  // Make sure the mapping exists.
  if (!$mapping) {
    return;
  }

  $schema_type = $mapping->getSchemaType();
  $schema_properties = $mapping->getSchemaProperties();
  $supports_subtyping = $mapping->supportsSubtyping();
}

/**
 * Alter the Schema.org roperty JSON-LD data for an entity's field item.
 */
function hook_schemadotorg_jsonld_field_item_alter(&$value, \Drupal\Core\Field\FieldItemInterface $item) {
  // Get entity information.
  $entity = $item->getEntity();
  $entity_type_id = $entity->getEntityTypeId();
  $bundle = $entity->bundle();

  // Get field information.
  $field_storage_definition = $item->getFieldDefinition()->getFieldStorageDefinition();
  $field_name = $item->getName();
  $field_type = $field_storage_definition->getType();
  $field_property_names = $item->getFieldDefinition()->getFieldStorageDefinition()->getPropertyNames();

  // Get main property information.
  $main_property_name = $field_storage_definition->getMainPropertyName();
  $main_property_definition = $field_storage_definition->getPropertyDefinition($main_property_name);
  $main_property_data_type = $main_property_definition->getDataType();

  // Get Schema.org mapping.
  /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
  $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');
  $mapping = $mapping_storage->loadByEntity($entity);
  $schema_type = $mapping->getSchemaType();
  $schema_property = $mapping->getSchemaPropertyMapping($field_name);

  // @todo Massage the data.
}

/**
 * @} End of "addtogroup hooks".
 */
