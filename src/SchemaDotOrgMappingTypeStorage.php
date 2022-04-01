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

}
