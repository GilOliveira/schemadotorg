<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for "schemadotorg_mapping_type" configuration entities.
 */
class SchemaDotOrgMappingTypeStorage extends ConfigEntityStorage implements SchemaDotOrgMappingTypeStorageInterface {

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
  public function getDefaultSchemaTypeBundle($entity_type_id, $type) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return NULL;
    }

    $schema_types = $mapping_type->get('default_schema_types') ?: [];
    $bundles = array_flip($schema_types);
    return $bundles[$type] ?? NULL;
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

    $default_base_fields = $mapping_type->get('default_base_fields') ?: [];
    return $default_base_fields ? array_flip(array_filter($default_base_fields)) : [];
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
  public function getSchemaPropertyDefaults($entity_type_id) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return [];
    }

    $properties = $mapping_type->get('default_schema_properties') ?: [];
    return array_combine($properties, $properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyUnlimited($entity_type_id) {
    $mapping_type = $this->load($entity_type_id);
    $unlimited = [];

    if ($mapping_type) {
      $mapping_unlimited = $mapping_type->get('default_unlimited_fields') ?: [];
      if ($mapping_unlimited) {
        $unlimited += array_combine($mapping_unlimited, $mapping_unlimited);
      }
    }

    $default_unlimited = $this->configFactory
      ->get('schemadotorg.settings')
      ->get('schema_properties.default_unlimited_fields');
    if ($default_unlimited) {
      $unlimited += array_combine($default_unlimited, $default_unlimited);
    }

    return $unlimited;
  }

}
