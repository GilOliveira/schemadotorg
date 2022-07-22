<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for 'schemadotorg_mapping_type' storage.
 */
interface SchemaDotOrgMappingTypeStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Gets entity types that implement Schema.org.
   *
   * @return array
   *   Entity types that implement Schema.org.
   */
  public function getEntityTypes();

  /**
   * Gets entity types with bundles that implement Schema.org.
   *
   * @return array
   *   Entity types with bundles that implement Schema.org.
   */
  public function getEntityTypesWithBundles();

  /**
   * Get entity type bundles. (i.e node)
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   Entity type bundles.
   */
  public function getEntityTypeBundles();

  /**
   * Get entity type bundle definitions. (i.e node_type)
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface[]
   *   Entity type bundle definitions.
   */
  public function getEntityTypeBundleDefinitions();

}
