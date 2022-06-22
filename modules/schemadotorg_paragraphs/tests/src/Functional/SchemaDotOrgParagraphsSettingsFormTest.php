<?php

namespace Drupal\Tests\schemadotorg_paragraphs\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org paragraphs settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgParagraphsSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_paragraphs'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org paragraphs settings form.
   */
  public function testSettingsForm() {
    $this->assertSaveSettingsConfigForm('schemadotorg_paragraphs.settings', '/admin/config/search/schemadotorg/settings/properties');
  }

}
