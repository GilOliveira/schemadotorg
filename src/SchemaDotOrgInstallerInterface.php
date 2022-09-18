<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org installer interface.
 */
interface SchemaDotOrgInstallerInterface {

  /**
   * Check installation requirements.
   *
   * @param string $phase
   *   The phase in which requirements are checked.
   *
   * @return array
   *   An associative array containing installation requirements.
   */
  public function requirements($phase);

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
   * Download and cleanup Schema.org CSV data.
   */
  public function downloadCsvData();

  /**
   * Import Schema.org types and properties tables.
   */
  public function importTables();

}
