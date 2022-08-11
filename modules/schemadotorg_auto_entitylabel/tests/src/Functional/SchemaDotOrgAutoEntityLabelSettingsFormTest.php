<?php

namespace Drupal\Tests\schemadotorg_auto_entitylabel\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org inline entity form settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgAutoEntityLabelSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_auto_entitylabel'];

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
    $this->assertSaveSettingsConfigForm('schemadotorg_auto_entitylabel.settings', '/admin/config/search/schemadotorg/settings/types');
  }

}
