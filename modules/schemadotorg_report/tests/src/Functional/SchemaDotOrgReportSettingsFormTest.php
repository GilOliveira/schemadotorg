<?php

namespace Drupal\Tests\schemadotorg_report\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org report settings form.
 *
 * @covers \Drupal\schemadotorg\Form\SchemaDotOrgReportSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgReportSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_report'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org report settings form.
   */
  public function testSchemaDotOrgReportSettingsForm() {
    $this->assertSaveSettingsConfigForm(
      'schemadotorg_report.settings',
      '/admin/config/search/schemadotorg/settings/references'
    );
  }

}
