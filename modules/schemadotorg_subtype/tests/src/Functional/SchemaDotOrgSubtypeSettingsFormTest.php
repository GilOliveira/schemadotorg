<?php

namespace Drupal\Tests\schemadotorg_subtype\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org subtype settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSubtypeSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_subtype'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org inline entity form settings form.
   */
  public function testSettingsForm() {
    $this->assertSaveSettingsConfigForm('schemadotorg_subtype.settings', '/admin/config/search/schemadotorg/settings');
  }

}
