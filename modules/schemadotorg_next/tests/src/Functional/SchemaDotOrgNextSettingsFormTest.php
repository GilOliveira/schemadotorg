<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_next\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org Next.js settings form.
 *
 * @covers \Drupal\schemadotorg_next\Form\SchemaDotOrgJsonLdSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgNextSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_next'];
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
   * Test Schema.org Next.js settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_next.settings', '/admin/config/search/schemadotorg/settings/next');
  }

}
