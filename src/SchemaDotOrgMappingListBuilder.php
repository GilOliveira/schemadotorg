<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Schema.org mappings.
 */
class SchemaDotOrgMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['entity_type'] = [
      'data' => $this->t('Type'),
      'width' => '15%',
    ];
    $header['bundle_label'] = [
      'data' => $this->t('Name'),
      'width' => '15%',
    ];
    $header['bundle_id'] = [
      'data' => $this->t('ID'),
      'width' => '15%',
    ];
    $header['schema_type'] = [
      'data' => $this->t('Schema.org type'),
      'width' => '15%',
    ];
    $header['schema_properties'] = [
      'data' => $this->t('Scheme.org properties'),
      'width' => '40%',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */

    $target_entity_type_definition = $entity->getTargetEntityTypeDefinition();
    $target_entity_type_bundle_definition = $entity->getTargetEntityTypeBundleDefinition();
    $row['entity_type'] = $target_entity_type_bundle_definition
      ? $target_entity_type_bundle_definition->getLabel()
      : $target_entity_type_definition->getLabel();

    $entity_type_bundle = $entity->getTargetEntityBundleEntity();
    $row['bundle_label'] = $entity_type_bundle ? $entity_type_bundle->label() : '';

    $row['bundle_id'] = $entity_type_bundle ? $entity_type_bundle->id() : '';

    $row['schema_type'] = $entity->getSchemaType();

    $properties = [];
    foreach ($entity->getSchemaProperties() as $mapping) {
      $properties[] = $mapping['property'];
    }
    $row['schema_properties'] = implode('; ', $properties);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (!$this->moduleHandler()->moduleExists('schemadotorg_ui')) {
      $operations['edit']['title'] = $this->t('View');
    }
    return $operations;
  }

}
