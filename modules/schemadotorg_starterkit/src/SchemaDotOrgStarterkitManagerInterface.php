<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit;

/**
 * Schema.org starterkit manager interface.
 */
interface SchemaDotOrgStarterkitManagerInterface {

  /**
   * Preinstall a Schema.org Blueprints starterkit.
   *
   * @param string $module
   *   A module.
   */
  public function preinstall(string $module): void;

}
