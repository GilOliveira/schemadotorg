<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Schema.org entity type manager service.
 */
class SchemaDotOrgEntityTypeManager implements SchemaDotOrgEntityTypeManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SchemaDotOrgInstaller object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get entity types that implement Schema.org.
   *
   * @return array
   *   Entity types that implement Schema.org.
   */
  public function getEntityTypes() {
    return [
      'block_content',
      'node',
      'media',
      'paragraph',
      'user',
    ];
  }

}
