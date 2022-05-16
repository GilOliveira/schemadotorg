<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "schemadotorg_mapping" configuration entities.
 */
class SchemaDotOrgMappingStorage extends ConfigEntityStorage implements SchemaDotOrgMappingStorageInterface {

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
  public function getSchemaPropertyName($entity_type_id, $bundle, $field_name) {
    /** @var SchemaDotOrgMappingInterface $entity */
    $entity = $this->load($entity_type_id . '.' . $bundle);
    if (!$entity) {
      return NULL;
    }
    return $entity->getSchemaPropertyMapping($field_name) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyRangeIncludes($entity_type_id, $bundle, $field_name) {
    $property = $this->getSchemaPropertyName($entity_type_id, $bundle, $field_name);
    $property_definition = $this->schemaTypeManager->getProperty($property);
    return $property_definition
      ? $this->schemaTypeManager->parseIds($property_definition['range_includes'])
      : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyTargetMappings($entity_type_id, $bundle, $field_name, $target_type) {
    $range_includes = $this->getSchemaPropertyRangeIncludes($entity_type_id, $bundle, $field_name);
    $subtypes = $this->schemaTypeManager->getAllSubTypes($range_includes);
    $entity_ids = $this->getQuery()
      ->condition('target_entity_type_id', $target_type)
      ->condition('type', $subtypes, 'IN')
      ->execute();
    if (!$entity_ids) {
      return [];
    }

    return $this->loadMultiple($entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyTargetSchemaTypes($entity_type_id, $bundle, $field_name, $target_type) {
    $range_includes = $this->getSchemaPropertyRangeIncludes($entity_type_id, $bundle, $field_name);
    return $this->getRangeIncludesTargetSchemaTypes($target_type, $range_includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyTargetBundles($entity_type_id, $bundle, $field_name, $target_type) {
    $range_includes = $this->getSchemaPropertyRangeIncludes($entity_type_id, $bundle, $field_name);
    return $this->getRangeIncludesTargetBundles($target_type, $range_includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getRangeIncludesTargetBundles($target_type, array $range_includes) {
    return $this->getRangeIncludesTargets($target_type, $range_includes, 'bundles');
  }

  /**
   * {@inheritdoc}
   */
  public function getRangeIncludesTargetSchemaTypes($target_type, array $range_includes) {
    return $this->getRangeIncludesTargets($target_type, $range_includes, 'schema_types');
  }

  /**
   * Gets the Schema.org range includes targets (bundles or schema_types).
   *
   * @param string $target_type
   *   The target entity type ID.
   * @param array $range_includes
   *   An array of Schema.org types.
   * @param string $target
   *   The target (bundle or Schema.org type).
   *
   * @return array
   *   The Schema.org range includes targets (bundles or schema_types).
   */
  protected function getRangeIncludesTargets($target_type, array $range_includes, $target) {
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
    $method = ($target === 'schema_types') ? 'getSchemaType' : 'getTargetBundle';
    $targets = [];
    foreach ($entities as $entity) {
      $target = $entity->$method();
      $targets[$target] = $target;
    }
    return $targets;
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
