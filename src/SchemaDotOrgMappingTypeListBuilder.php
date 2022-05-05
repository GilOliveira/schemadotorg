<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Schema.org mapping types.
 */
class SchemaDotOrgMappingTypeListBuilder extends SchemaDotOrgConfigEntityListBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $details_toggle = $this->getDetailsToggle();
    if ($details_toggle) {
      $header['entity_type'] = [
        'data' => $this->t('Type'),
      ];
      $header['recommended_schema_types'] = [
        'data' => $this->t('Recommended Schema.org types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '12%',
      ];
      $header['default_schema_types'] = [
        'data' => $this->t('Default Schema.org types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '12%',
      ];
      $header['default_schema_type_properties'] = [
        'data' => $this->t('Defined Schema.org type'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '12%',
      ];
      $header['default_schema_type_subtypes'] = [
        'data' => $this->t('Schema.org subtypes'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '12%',
      ];
      $header['default_base_fields'] = [
        'data' => $this->t('Base field mappings'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '12%',
      ];
      $header['default_field_weights'] = [
        'data' => $this->t('Field weights'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '12%',
      ];
      $header['default_field_groups'] = [
        'data' => $this->t('Field groups'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '12%',
      ];
    }
    else {
      $header['entity_type'] = [
        'data' => $this->t('Type'),
        'width' => '100%',
      ];
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Type.
    $row['entity_type'] = $entity->label();
    $details_toggle = $this->getDetailsToggle();
    if ($details_toggle) {

      // Recommended Schema.org types.
      $recommended_schema_types = $entity->get('recommended_schema_types');
      $recommended_schema_type_labels = [];
      foreach ($recommended_schema_types as $recommended_schema_type) {
        $recommended_schema_type_labels[$recommended_schema_type['label']] = $recommended_schema_type['label'];
      }
      $row['recommended_schema_types'] = $this->buildItems($recommended_schema_type_labels);

      // Default schema types.
      $row['default_schema_types'] = $this->buildAssociationItems($entity->get('default_schema_types'));

      // Default schema properties.
      $keys = array_keys($entity->get('default_schema_type_properties'));
      $row['default_schema_type_properties'] = $this->buildItems($keys);

      // Default subtypes.
      $row['default_schema_type_subtypes'] = $this->buildItems($entity->get('default_schema_type_subtypes'));

      // Default base fields mapping.
      $row['default_base_fields'] = $this->buildAssociationItems($entity->get('default_base_fields'));

      // Default field weights.
      $row['default_field_weights'] = $this->buildItems($entity->get('default_field_weights'));

      // Default field groups.
      $default_field_groups = $entity->get('default_field_groups');
      $group_labels = [];
      foreach ($default_field_groups as $default_field_group) {
        $group_labels[] = $default_field_group['label'];
      }
      $row['default_field_groups'] = $this->buildItems($group_labels);
    }

    return $row + parent::buildRow($entity);
  }

}
