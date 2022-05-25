<?php

namespace Drupal\Tests\schemadotorg_jsonld_preview\Functional;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD preview.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdPreviewTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['user', 'node', 'schemadotorg_jsonld', 'schemadotorg_jsonld_preview'];

  /**
   * Test Schema.org list builder enhancements.
   */
  public function testSchemaDotOrgListBuilder() {
    $assert_session = $this->assertSession();

    $account = $this->createUser(['access content', 'view schemadotorg jsonld']);

    // Create Thing content type with a Schema.org mapping.
    $this->drupalCreateContentType(['type' => 'thing']);
    $node = $this->drupalCreateNode([
      'type' => 'thing',
      'title' => 'Something',
    ]);
    $node->save();

    // Check that JSON-LD preview is not displayed for users without permission.
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON-LD');

    // Check that JSON-LD preview is not displayed without a mapping.
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON-LD');

    // Create a Schema.org mapping for Thing.
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'type' => 'Thing',
      'properties' => [
        'title' => 'name',
      ],
    ])->save();

    // Check that JSON-LD preview is not displayed for users without permission.
    $this->drupalLogout();
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON-LD');

    // Check that JSON-LD preview is not displayed.
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl());
    $assert_session->responseContains('Schema.org JSON-LD');
  }

}
