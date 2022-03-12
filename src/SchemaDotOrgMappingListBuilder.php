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
    $header['id'] = $this->t('id');
    $header['targetEntityType'] = $this->t('targetEntityType');
    $header['bundle'] = $this->t('bundle');
    $header['type'] = $this->t('type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    $row['id'] = $entity->id();
    $row['targetEntityType'] = $entity->get('targetEntityType');
    $row['bundle'] = $entity->get('bundle');
    $row['type'] = $entity->get('type');
    return $row + parent::buildRow($entity);
  }

}
