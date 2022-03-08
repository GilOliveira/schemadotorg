<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity type manager interface.
 */
interface SchemaDotOrgEntityTypeManagerInterface {

  /**
   * Get entity types that implement Schema.org.
   *
   * @return array
   *   Entity types that implement Schema.org.
   */
  public function getEntityTypes();

}
