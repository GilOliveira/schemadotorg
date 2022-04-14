<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Schema.org mapping types.
 */
class SchemaDotOrgMappingTypeListBuilder extends ConfigEntityListBuilder {

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
  public function buildHeader() {
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
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Type.
    $row['entity_type'] = $entity->label();

    // Recommended Schema.org types.
    $recommended_schema_types = $entity->get('recommended_schema_types');
    $recommended_schema_type_labels = [];
    foreach ($recommended_schema_types as $recommended_schema_type) {
      $recommended_schema_type_labels[$recommended_schema_type['label']] = $recommended_schema_type['label'];
    }
    $row['recommended_schema_types'] = implode(', ', $recommended_schema_type_labels);

    // Default schema types.
    $row['default_schema_types'] = implode(', ', $entity->get('default_schema_types'));

    // Default schema properties.
    $keys = array_keys($entity->get('default_schema_type_properties'));
    $row['default_schema_type_properties'] = implode(', ', $keys);

    // Default subtypes.
    $row['default_schema_type_subtypes'] = implode(', ', $entity->get('default_schema_type_subtypes'));

    // Default base fields mapping.
    $default_base_fields = $entity->get('default_base_fields');
    $properties = [];
    foreach ($default_base_fields as $default_base_field_properties) {
      if ($default_base_field_properties) {
        $default_base_field_properties = array_filter($default_base_field_properties);
        $properties += array_combine($default_base_field_properties, $default_base_field_properties);
      }
    }
    ksort($properties);
    $row['default_base_fields'] = implode(', ', $properties);

    // Default field weights.
    $row['default_field_weights'] = implode(', ', $entity->get('default_field_weights'));

    // Default field groups.
    $default_field_groups = $entity->get('default_field_groups');
    $group_labels = [];
    foreach ($default_field_groups as $default_field_group) {
      $group_labels[] = $default_field_group['label'];
    }
    $row['default_field_groups'] = implode(', ', $group_labels);

    return $row + parent::buildRow($entity);
  }

}
