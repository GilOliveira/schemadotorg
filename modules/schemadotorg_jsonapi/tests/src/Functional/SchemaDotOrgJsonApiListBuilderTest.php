<?php

namespace Drupal\Tests\schemadotorg_jsonapi\Functional;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON:API list builder enhancements.
 *
 * @covers \Drupal\schemadotorg_jsonapi\EventSubscriber\SchemaDotOrgJsonApiEventSubscriber
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiListBuilderTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['user', 'node', 'schemadotorg_jsonapi'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Thing content type with a Schema.org mapping.
    $this->drupalCreateContentType(['type' => 'thing']);
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'type' => 'Thing',
    ])->save();

    // Create Person content type with a Schema.org mapping.
    $this->drupalCreateContentType(['type' => 'person']);
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'person',
      'type' => 'Person',
    ])->save();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org list builder enhancements.
   */
  public function testSchemaDotOrgListBuilder() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/structure/schemadotorg');

    // Check JSON:API header.
    $assert_session->responseContains('<th class="priority-low" width="27%">JSON:API</th>');

    // Check link to /jsonapi/ContentThing JSON:API endpoint because
    // '/jsonapi/Thing' is already being used by the schema_thing taxonomy.
    $assert_session->linkExists('/jsonapi/ContentThing');

    // Check link to /jsonapi/Person JSON:API endpoint exists.
    $assert_session->linkExists('/jsonapi/Person');

  }

}
