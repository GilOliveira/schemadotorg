<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "schemadotorg_mapping_type" configuration entities.
 */
class SchemaDotOrgMappingTypeStorage extends ConfigEntityStorage implements SchemaDotOrgMappingTypeStorageInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    $entity_types = array_keys($this->loadMultiple());
    return array_combine($entity_types, $entity_types);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaTypeBundles($entity_type_id, $type) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return [];
    }

    $schema_types = $mapping_type->get('default_schema_types') ?: [];
    $bundles = [];
    foreach ($schema_types as $bundle => $schema_type) {
      if ($type === $schema_type) {
        $bundles[$bundle] = $bundle;
      }
    }
    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaType($entity_type_id, $bundle) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return NULL;
    }

    $schema_types = $mapping_type->get('default_schema_types') ?: [];
    return $schema_types[$bundle] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendedSchemaTypes($entity_type_id) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return [];
    }

    return $mapping_type->get('recommended_schema_types') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldMappings($entity_type_id) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return [];
    }

    $base_fields = $mapping_type->get('default_base_fields') ?: [];
    $base_fields = array_filter($base_fields);
    if (empty($base_fields)) {
      return [];
    }

    $mappings = [];
    foreach ($base_fields as $field_name => $properties) {
      foreach ($properties as $property) {
        $mappings[$property] = $field_name;
      }
    }
    return $mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldNames($entity_type_id) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return [];
    }

    $default_base_fields = $mapping_type->get('default_base_fields') ?: [];
    $base_field_names = array_keys($default_base_fields);
    return array_combine($base_field_names, $base_field_names);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeBundles() {
    $entity_types = $this->getEntityTypes();

    $items = [];
    foreach ($entity_types as $entity_type_id) {
      // Make sure the entity is supported.
      if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
        continue;
      }

      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      // Make sure the entity has a field UI.
      $route_name = $entity_type->get('field_ui_base_route');
      if (!$route_name) {
        continue;
      }

      // Media bundles are not support because the add media form is
      // not reusable.
      if ($entity_type_id === 'media') {
        continue;
      }

      // Make sure the bundle entity exists.
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      if (!$bundle_entity_type_id) {
        continue;
      }

      $items[$entity_type_id] = $entity_type;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeBundleDefinitions() {
    $items = [];
    $entity_types = $this->getEntityTypeBundles();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      $items[$entity_type_id] = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
    }
    return $items;
  }

}
