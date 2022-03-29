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
    $mapping = $entity->getSchemaPropertyMapping($field_name) ?: [];
    return $mapping['property'] ?? NULL;
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
  public function getSchemaPropertyTargetBundles($entity_type_id, $bundle, $field_name, $target_type) {
    $range_includes = $this->getSchemaPropertyRangeIncludes($entity_type_id, $bundle, $field_name);
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

    $bundles = [];
    foreach ($entities as $entity) {
      $bundles[$entity->getTargetBundle()] = $entity->getTargetBundle();
    }
    return $bundles;
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

}
