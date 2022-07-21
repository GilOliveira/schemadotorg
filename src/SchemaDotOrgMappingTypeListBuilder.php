<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Schema.org mapping types.
 */
class SchemaDotOrgMappingTypeListBuilder extends SchemaDotOrgConfigEntityListBuilderBase {

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
    $details_toggle = $this->getDetailsToggle();
    if ($details_toggle) {
      $header['entity_type'] = [
        'data' => $this->t('Type'),
      ];
      $header['default_schema_types'] = [
        'data' => $this->t('Default Schema.org types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ];
      $header['recommended_schema_types'] = [
        'data' => $this->t('Recommended Schema.org types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ];
      $header['multiple'] = [
        'data' => $this->t('Multiple'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ];
      $header['default_schema_type_properties'] = [
        'data' => $this->t('Default Schema.org type properties'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ];
      $header['default_base_fields'] = [
        'data' => $this->t('Base field mappings'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ];
      $header['default_field_weights'] = [
        'data' => $this->t('Field weights'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ];
      $header['default_field_groups'] = [
        'data' => $this->t('Field groups'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10s%',
      ];
    }
    else {
      $header['entity_type'] = [
        'data' => $this->t('Type'),
        'width' => '30%',
      ];
      $header['default_schema_types'] = [
        'data' => $this->t('Default Schema.org types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '30%',
      ];
      $header['recommended_schema_types'] = [
        'data' => $this->t('Recommended Schema.org types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '30%',
      ];
      $header['multiple'] = [
        'data' => $this->t('Multiple'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
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

    // Default schema types.
    $row['default_schema_types'] = $this->buildAssociationItems($entity->get('default_schema_types'));

    // Recommended Schema.org types.
    $recommended_schema_types = $entity->get('recommended_schema_types');
    $recommended_schema_type_labels = [];
    foreach ($recommended_schema_types as $recommended_schema_type) {
      $recommended_schema_type_labels[$recommended_schema_type['label']] = $recommended_schema_type['label'];
    }
    $row['recommended_schema_types'] = $this->buildItems($recommended_schema_type_labels);

    // Multiple.
    $row['multiple'] = $entity->get('multiple') ? $this->t('Yes') : $this->t('No');

    $details_toggle = $this->getDetailsToggle();
    if ($details_toggle) {

      // Default Schema.org type properties.
      $row['default_schema_type_properties'] = $this->buildAssociationItems($entity->get('default_schema_type_properties'));

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

    $row = $row + parent::buildRow($entity);

    // Highlight missing entity types.
    if (!$this->entityTypeManager->hasDefinition($entity->id())) {
      $row = [
        'data' => $row,
        'class' => ['color-warning'],
      ];
    }

    return $row;
  }

}
