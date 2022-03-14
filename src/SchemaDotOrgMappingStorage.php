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
   * {@inheritdoc}
   */
  public function isBundleMapped($entity_type_id, $bundle) {
    return (boolean) $this->getQuery()
      ->condition('targetEntityType', $entity_type_id)
      ->condition('bundle', $bundle)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function isSchemaTypeMapped($entity_type_id, $type) {
    return (boolean) $this->getQuery()
      ->condition('targetEntityType', $entity_type_id)
      ->condition('type', $type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySchemaType($entity_type_id, $type) {
    $entities = $this->loadByProperties([
      'targetEntityType' => $entity_type_id,
      'type' => $type,
    ]);
    return ($entities) ? reset($entities) : NULL;
  }

}
