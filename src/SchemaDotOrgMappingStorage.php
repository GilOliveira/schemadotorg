<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "schemadotorg_mapping" configuration entities.
 */
class SchemaDotOrgMappingStorage extends ConfigEntityStorage implements SchemaDotOrgMappingStorageInterface {

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaNames = $container->get('schemadotorg.names');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityMapped(EntityInterface $entity) {
    return $this->isBundleMapped($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function isBundleMapped($entity_type_id, $bundle) {
    return (boolean) $this->getQuery()
      ->condition('target_entity_type_id', $entity_type_id)
      ->condition('target_bundle', $bundle)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaType($entity_type_id, $bundle) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    $entity = $this->load($entity_type_id . '.' . $bundle);
    if (!$entity) {
      return NULL;
    }
    return $entity->getSchemaType();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyName($entity_type_id, $bundle, $field_name) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    $entity = $this->load($entity_type_id . '.' . $bundle);
    if (!$entity) {
      return NULL;
    }
    return $entity->getSchemaPropertyMapping($field_name) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyRangeIncludes($schema_type, $schema_property) {
    $schema_properties_range_includes = $this->configFactory
      ->get('schemadotorg.settings')
      ->get("schema_properties.range_includes");
    $range_includes = $schema_properties_range_includes["$schema_type--$schema_property"]
      ?? $schema_properties_range_includes[$schema_property]
      ?? $this->schemaTypeManager->getPropertRangeIncludes($schema_property);
    return array_combine($range_includes, $range_includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyTargetBundles($target_type, $schema_type, $schema_property) {
    $range_includes = $this->getSchemaPropertyRangeIncludes($schema_type, $schema_property);
    return $this->getRangeIncludesTargetBundles($target_type, $range_includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getRangeIncludesTargetBundles($target_type, array $range_includes) {
    // Remove 'Thing' because it is too generic.
    unset($range_includes['Thing']);

    $subtypes = $this->schemaTypeManager->getAllSubTypes($range_includes);
    $entity_ids = $this->getQuery()
      ->condition('target_entity_type_id', $target_type)
      ->condition('type', $subtypes, 'IN')
      ->execute();
    if (!$entity_ids) {
      return [];
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $entities */
    $entities = $this->loadMultiple($entity_ids);
    $target_bundles = [];
    foreach ($entities as $entity) {
      $target = $entity->getTargetBundle();
      $target_bundles[$target] = $target;
    }
    return $target_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function isSchemaTypeMapped($entity_type_id, $type) {
    return (boolean) $this->getQuery()
      ->condition('target_entity_type_id', $entity_type_id)
      ->condition('type', $type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySchemaType($entity_type_id, $type) {
    $entities = $this->loadByProperties([
      'target_entity_type_id' => $entity_type_id,
      'type' => $type,
    ]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntity(EntityInterface $entity) {
    $entities = $this->loadByProperties([
      'target_entity_type_id' => $entity->getEntityTypeId(),
      'target_bundle' => $entity->bundle(),
    ]);
    return ($entities) ? reset($entities) : NULL;
  }

}
