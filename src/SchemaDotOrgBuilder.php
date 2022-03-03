<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Schema.org builder service.
 */
class SchemaDotOrgBuilder implements SchemaDotOrgBuilderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SchemaDotOrgBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

}
