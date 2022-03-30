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
      'width' => '20%',
    ];
    $header['default_schema_types'] = [
      'data' => $this->t('Default schema types'),
      'width' => '40%',
    ];
    $header['default_base_fields'] = [
      'data' => $this->t('Default base fields mapping'),
      'width' => '40%',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['entity_type'] = $entity->label();
    $row['default_schema_types'] = implode(', ', $entity->get('default_schema_types'));
    $row['default_base_fields'] = implode(', ', array_filter($entity->get('default_base_fields')));
    return $row + parent::buildRow($entity);
  }

}
