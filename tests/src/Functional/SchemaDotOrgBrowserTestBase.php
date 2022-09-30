<?php

namespace Drupal\Tests\schemadotorg\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\schemadotorg\Traits\SchemaDotOrgTestTrait;

/**
 * Defines an abstract test base for Schema.org tests.
 */
abstract class SchemaDotOrgBrowserTestBase extends BrowserTestBase {
  use SchemaDotOrgTestTrait;

  /**
   * Set default theme to stable.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg'];

  /**
   * Assert saving a settings form does not alter the expected values.
   *
   * @param string $name
   *   Configuration settings name.
   * @param string $path
   *   Configuration settings form path.
   */
  protected function assertSaveSettingsConfigForm($name, $path) {
    $assert_session = $this->assertSession();

    $expected_data = \Drupal::configFactory()->get($name)->getRawData();
    $this->drupalGet($path);
    $this->submitForm([], 'Save configuration');
    $assert_session->responseContains('The configuration options have been saved.');
    \Drupal::configFactory()->reset($name);
    $actual_data = \Drupal::configFactory()->get($name)->getRawData();
    $this->assertEquals($expected_data, $actual_data);
  }

}
