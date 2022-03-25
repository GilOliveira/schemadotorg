<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org installer interface.
 */
interface SchemaDotOrgInstallerInterface {

  /**
   * Installs the Schema.org module's properties and types.
   */
  public function install();

  /**
   * Gets Schema.org properties and types database schema.
   *
   * @return array
   *   A schema definition structure array.
   */
  public function schema();

  /**
   * Import Schema.org types and properties tables.
   */
  public function importTables();

}
