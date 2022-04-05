<?php

namespace Drupal\schemadotorg_report;

/**
 * Schema.org report references interface.
 */
interface SchemaDotOrgReportReferencesInterface {

  /**
   * The references cache id.
   */
  const CACHE_ID = 'schemadotorg_reports_references';

  /**
   * Get Schema.org references.
   *
   * @param string $type
   *   (optional) Schema.org type.
   *
   * @return array
   *   Schema.org references.
   */
  public function getReferences($type = NULL);

  /**
   * Reset Schema.org references cache.
   */
  public function resetReferences();

}
