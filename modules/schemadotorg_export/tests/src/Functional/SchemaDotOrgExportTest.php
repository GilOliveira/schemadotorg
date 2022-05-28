<?php

namespace Drupal\Tests\schemadotorg_export\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests for Schema.org export.
 *
 * @group schemadotorg
 */
class SchemaDotOrgExportTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'field',
    'field_ui',
    'schemadotorg_ui',
    'schemadotorg_export',
  ];

  /**
   * Test Schema.org descriptions.
   */
  public function testDescriptions() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer schemadotorg',
    ]);
    $this->drupalLogin($account);

    // Create the 'Thing' content type with type and alternateName fields.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Thing']]);
    $edit = [
      'subtyping[enable]' => TRUE,
      'properties[alternateName][field][name]' => '_add_',
      'properties[name][field][name]' => '_add_',
    ];
    $this->submitForm($edit, 'Save');

    // Check that 'Download CSV' link is added to the Schema.org mapping list.
    $this->drupalGet('/admin/config/search/schemadotorg');
    $assert_session->responseContains('<u>⇩</u> Download CSV');

    // Check Schema.org mapping CSV.
    $this->drupalGet('/admin/config/search/schemadotorg.csv');
    $assert_session->responseContains('entity_type,bundle,schema_type,schema_subtyping,schema_properties');
    $assert_session->responseContains('node,thing,Thing,Yes,"alternateName; name"');
  }

}
