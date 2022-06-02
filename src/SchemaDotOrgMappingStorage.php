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
  public function getSchemaPropertyTargetBundles($target_type, $schema_property, $schema_type = NULL) {
    // @todo Use Schema.org type to target just the main entity.
    $property_definition = $this->schemaTypeManager->getProperty($schema_property);
    $range_includes = $property_definition['range_includes'] ?? '';
    $range_includes = $this->schemaTypeManager->parseIds($range_includes);

    return $this->getRangeIncludesTargetBundles($target_type, $range_includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getRangeIncludesTargetBundles($target_type, array $range_includes) {
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

  /**
   * {@inheritdoc}
   */
  public function getSubtype(EntityInterface $entity) {
    $mapping = $this->loadByEntity($entity);

    // Make sure the mapping exists and supports subtyping.
    if (!$mapping || !$mapping->supportsSubtyping()) {
      return FALSE;
    }

    $bundle = $mapping->getTargetBundle();
    $subtype_field_name = $this->schemaNames->getSubtypeFieldName($bundle);
    if (!$entity->hasField($subtype_field_name)
      || empty($entity->{$subtype_field_name}->value)) {
      return FALSE;
    }

    return $entity->{$subtype_field_name}->value;
  }

}
