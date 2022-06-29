<?php

namespace Drupal\schemadotorg_jsonapi_preview;

/**
 * Schema.org JSON:API preview access checker interface.
 */
interface SchemaDotOrgJsonApiPreviewAccessCheckerInterface {

  /**
   * Checks JSON:API preview access for the current user and route.
   *
   * @return bool
   *   TRUE if the current user can access the preview.
   */
  public function access();

}
