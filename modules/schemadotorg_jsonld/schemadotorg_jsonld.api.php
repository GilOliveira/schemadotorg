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

/* ************************************************************************** */
// Custom data.
/* ************************************************************************** */

/**
 * Provide custom Schema.org JSON-LD data for a route..
 *
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match.
 *
 * @return array
 *   Custom Schema.org JSON-LD data.
 */
function hook_schemadotorg_jsonld(\Drupal\Core\Routing\RouteMatchInterface $route_match) {
  return [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'item' => [
          '@id' => 'https://example.com/dresses',
          'name' => 'Dresses',
        ],
      ],
      [
        '@type' => 'ListItem',
        'position' => 2,
        'item' => [
          '@id' => 'https://example.com/dresses/real',
          'name' => 'Real Dresses',
        ],
      ],
    ],
  ];
}

/**
 * Alter the Schema.org JSON-LD data for the current route.
 */
function hook_schemadotorg_jsonld_alter(array &$data, \Drupal\Core\Routing\RouteMatchInterface $route_match) {
  /** @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $manager */
  $manager = \Drupal::service('schemadotorg_jsonld.manager');
  $entity = $manager->getRouteMatchEntity($route_match);
  if (!$entity) {
    return;
  }

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

/* ************************************************************************** */
// Entity data.
/* ************************************************************************** */

/**
 * Provide custom Schema.org JSON-LD data for an entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity.
 *
 * @return array
 *   Custom entity Schema.org JSON-LD data.
 */
function hook_schemadotorg_jsonld_entity(\Drupal\Core\Entity\EntityInterface $entity) {
  // Define custom data for taxonomy vocabulary which is mapped via the term.
  if ($entity instanceof \Drupal\taxonomy\Entity\Vocabulary) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = \Drupal::entityTypeManager()
      ->getStorage('schemadotorg_mapping');
    $mappings = $mapping_storage->loadByProperties([
      'target_entity_type_id' => 'taxonomy_term',
      'target_bundle' => $entity->id(),
    ]);
    if (!$mappings) {
      return [];
    }

    $mapping = reset($mappings);
    $schema_type = $mapping->getSchemaType();
    if (!in_array($schema_type, ['DefinedTerm', 'CategoryCode'])) {
      return [];
    }

    $data = [];
    $data['@type'] = "{$schema_type}Set";
    $data['name'] = $entity->label();
    $description = $entity->getDescription();
    if ($description) {
      $data['description'] = $description;
    }
    // Provide a uniquey key for the returned data.
    $key = 'hook_schemadotorg_jsonld_entity_taxonomy-' . $entity->uuid();
    return [$key => $data];
  }
}

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

/* ************************************************************************** */
// Field item value.
/* ************************************************************************** */

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

  // Massage the data.
  // ...
}

/**
 * @} End of "addtogroup hooks".
 */
