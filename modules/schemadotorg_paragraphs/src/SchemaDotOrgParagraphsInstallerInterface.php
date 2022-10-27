<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_paragraphs;

/**
 * Schema.org paragraphs installer.
 */
interface SchemaDotOrgParagraphsInstallerInterface {

  /**
   * Create paragraph type icons.
   *
   * @param string $module_name
   *   The module name.
   */
  public function createParagraphTypeIcons(string $module_name): void;

}
