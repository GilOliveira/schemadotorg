<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_help;

/**
 * Schema.org help manager interface.
 */
interface SchemaDotOrgHelpManagerInterface {

  /**
   * Builds a help page for a Schema.org module's README.md contents.
   *
   * @param string $module_name
   *   The Schema.org Blueprints module name.
   *
   * @return array|null
   *   A render array containing the Schema.org module's README.md contents.
   */
  public function buildHelpPage(string $module_name): ?array;

  /**
   * Get a module's video as a renderable array.
   *
   * @return array
   *   A module's videos as a renderable array.
   */
  public function buildVideosPage(): array;

}
