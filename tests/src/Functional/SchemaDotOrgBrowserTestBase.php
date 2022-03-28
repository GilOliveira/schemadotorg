<?php

namespace Drupal\Tests\schemadotorg\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines an abstract test base for Schema.org tests.
 */
abstract class SchemaDotOrgBrowserTestBase extends BrowserTestBase {

  /**
   * Set default theme to stable.
   *
   * @var string
   */
  protected $defaultTheme = 'stable';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg'];

}
