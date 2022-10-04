<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

/**
 * Schema.org config manager interface.
 */
interface SchemaDotOrgConfigManagerInterface {

  /**
   * Repair configuration.
   */
  public function repair(): void;

}
