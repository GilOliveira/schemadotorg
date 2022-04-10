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
    $header['default_schema_types'] = [
      'data' => $this->t('Default schema types'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => '25%',
    ];
    $header['default_base_fields'] = [
      'data' => $this->t('Default base fields mapping'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => '25%',
    ];
    $header['default_field_groups'] = [
      'data' => $this->t('Default field groups'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => '25%',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Type.
    $row['entity_type'] = $entity->label();

    // Default schema types.
    $row['default_schema_types'] = implode(', ', $entity->get('default_schema_types'));

    // Default base fields mapping.
    $default_base_fields = $entity->get('default_base_fields');
    $properties = [];
    foreach ($default_base_fields as $default_base_field_properties) {
      if ($default_base_field_properties) {
        $properties = array_merge($properties, $default_base_field_properties);
      }
    }
    $row['default_base_fields'] = implode(', ', array_filter($properties));

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
