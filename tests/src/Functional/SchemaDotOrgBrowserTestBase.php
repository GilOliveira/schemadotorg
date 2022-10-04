<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\schemadotorg\Traits\SchemaDotOrgTestTrait;

/**
 * Defines an abstract test base for Schema.org tests.
 */
abstract class SchemaDotOrgBrowserTestBase extends BrowserTestBase {
  use SchemaDotOrgTestTrait;

  // phpcs:disable
  /**
   * Set default theme to stable.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';
  // phpcs:enable

  // phpcs:disable
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg'];
  // phpcs:enable

  /**
   * Assert saving a settings form does not alter the expected values.
   *
   * @param string $name
   *   Configuration settings name.
   * @param string $path
   *   Configuration settings form path.
   */
  protected function assertSaveSettingsConfigForm(string $name, string $path): void {
    $assert_session = $this->assertSession();

    $expected_data = $this->config($name)->getRawData();
    $this->drupalGet($path);
    $this->submitForm([], 'Save configuration');
    $assert_session->responseContains('The configuration options have been saved.');
    \Drupal::configFactory()->reset($name);
    $actual_data = \Drupal::configFactory()->get($name)->getRawData();
    $this->assertEquals($expected_data, $actual_data);
  }

}
