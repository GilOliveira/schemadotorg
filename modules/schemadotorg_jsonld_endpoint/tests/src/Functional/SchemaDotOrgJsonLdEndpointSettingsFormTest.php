<?php

namespace Drupal\Tests\schemadotorg_jsonld_endpoint\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD endpoint settings form.
 *
 * @covers \Drupal\schemadotorg_jsonapi\Form\SchemaDotOrgDemoSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdEndpointSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_jsonapi', 'schemadotorg_jsonld_endpoint'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org JSON-LD settings form.
   */
  public function testSettingsForm() {
    $this->assertSaveSettingsConfigForm('schemadotorg_jsonld_endpoint.settings', '/admin/config/search/schemadotorg/settings/jsonapi');
  }

}
