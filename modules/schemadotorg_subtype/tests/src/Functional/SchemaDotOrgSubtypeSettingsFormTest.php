<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_subtype\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org subtype settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSubtypeSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_subtype'];
  // phpcs:enable

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
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_subtype.settings', '/admin/config/search/schemadotorg/settings/subtype');
  }

}
