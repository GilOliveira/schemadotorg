<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Schema.org mappings.
 */
class SchemaDotOrgMappingListBuilder extends SchemaDotOrgConfigEntityListBuilderBase {

  /**
   * The entity type manager service.
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
      'width' => '10%',
    ];
    $header['bundle_label'] = [
      'data' => $this->t('Name'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => '40%',
    ];
    $header['schema_type'] = [
      'data' => $this->t('Schema.org type'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => '40%',
    ];
    $header['schema_subtype'] = [
      'data' => $this->t('Schema.org subtyping'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => '15%',
    ];

    $details_toggle = $this->getDetailsToggle();
    if ($details_toggle) {
      $header['entity_type']['width'] = '10%';
      $header['bundle_label']['width'] = '15%';
      $header['schema_type']['width'] = '15%';
      $header['schema_subtype']['width'] = '10%';
      $header['schema_properties'] = [
        'data' => $this->t('Scheme.org properties'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '25%',
      ];
      $header['schema_relationships'] = [
        'data' => $this->t('Schema.org relationships'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '25%',
      ];
    }

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
    $row['bundle_label'] = $entity_type_bundle
      ? ['data' => $entity_type_bundle->toLink($entity_type_bundle->label(), 'edit-form')->toRenderable()]
      : '';

    $row['schema_type'] = $entity->getSchemaType();

    $row['schema_subtype'] = $entity->supportsSubtyping() ? $this->t('Yes') : $this->t('No');

    $details_toggle = $this->getDetailsToggle();
    if ($details_toggle) {
      $row['schema_properties'] = implode('; ', $entity->getSchemaProperties());

      $row['schema_relationships'] = $this->buildSchemaRelationships($entity);
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * Build the Schema.org mapping properties range includes relationships.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity
   *   The Schema.org mapping.
   *
   * @return array[]
   *   A renderable array containing Schema.org mapping properties range
   *   includes relationships.
   */
  protected function buildSchemaRelationships(SchemaDotOrgMappingInterface $entity) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->getStorage();

    /** @var \Drupal\field\FieldConfigStorage $field_config_storage */
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');

    $properties = $entity->getSchemaProperties();
    $target_entity_type_id = $entity->getTargetEntityTypeId();
    $target_bundle = $entity->getTargetBundle();

    $relationships = [];
    foreach ($properties as $field_name => $property) {
      $field_config_id = $target_entity_type_id . '.' . $target_bundle . '.' . $field_name;
      /** @var \Drupal\field\FieldConfigInterface $field_config */
      $field_config = $field_config_storage->load($field_config_id);
      if (!$field_config) {
        continue;
      }

      $is_entity_reference = in_array($field_config->getType(), ['entity_reference', 'entity_reference_revisions']);
      $has_schemadotorg_range_includes_handler = ($field_config->getSetting('handler') === 'schemadotorg_range_includes');
      if (!$is_entity_reference || !$has_schemadotorg_range_includes_handler) {
        continue;
      }

      $target_type = $field_config->getSetting('target_type');
      $target_schema_types = $mapping_storage->getSchemaPropertyTargetSchemaTypes(
        $target_entity_type_id,
        $target_bundle,
        $field_name,
        $target_type
      );
      $relationships[$field_name] = [
        'property' => ['#markup' => $property],
        'relationship' => ['#markup' => ' → '],
        'schema_types' => [
          '#markup' => ($target_schema_types)
          ? implode(', ', $target_schema_types)
          : $this->t('Missing'),
        ],
        '#prefix' => $relationships ? '<br/><br/>' : '',
      ];
    }

    return ['data' => $relationships];
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

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('target_entity_type_id');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // Override the default load method to not sort mapping by label
    // and instead sort them by the id.
    // @see \Drupal\Core\Config\Entity\ConfigEntityListBuilder::load
    // @see \Drupal\Core\Config\Entity\ConfigEntityBase::sort
    // @see \Drupal\Core\Entity\EntityListBuilder::getEntityIds
    $entity_ids = $this->getEntityIds();
    return $this->storage->loadMultipleOverrideFree($entity_ids);
  }

}
