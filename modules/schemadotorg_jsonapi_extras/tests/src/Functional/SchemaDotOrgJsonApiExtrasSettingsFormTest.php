<?php

namespace Drupal\Tests\schemadotorg_jsonapi_extras\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON:API settings form.
 *
 * @covers \Drupal\schemadotorg_jsonapi_extras\Form\SchemaDotOrgJsonApiExtrasSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiExtrasSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_jsonapi_extras'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org JSON:API settings form.
   */
  public function testSchemaDotOrgSettingsForm() {
    $this->assertSaveSettingsConfigForm('schemadotorg_jsonapi_extras.settings', '/admin/structure/schemadotorg/settings/jsonapi');
  }

}
