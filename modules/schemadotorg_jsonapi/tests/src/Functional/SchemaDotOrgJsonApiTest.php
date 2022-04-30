<?php

namespace Drupal\Tests\schemadotorg_jsonapi\Functional;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests for Schema.org JSON:API.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'node', 'schemadotorg_jsonapi'];

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The test node's Schema.org mapping.
   *
   * @var \Drupal\schemadotorg\Entity\SchemaDotOrgMapping
   */
  protected $nodeMapping;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Event node with field.
    $this->drupalCreateContentType([
      'type' => 'event',
      'name' => 'Event',
    ]);
    $this->createSchemaDotOrgField('node', 'event');
    $this->createSchemaDotOrgSubTypeField('node', 'event');

    // Create Event with mapping.
    $node_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'event',
      'type' => 'Event',
      'subtype' => TRUE,
      'properties' => [
        'title' => 'name',
        'schema_alternate_name' => 'alternateName',
      ],
    ]);
    $node_mapping->save();
    $this->nodeMapping = $node_mapping;

    // Create a node.
    $this->node = $this->drupalCreateNode([
      'type' => 'event',
      'title' => 'A event',
      'schema_alternate_name' => ['value' => 'Another event'],
    ]);

    // JSON:API routes are not being rebuilt.
    // @see \Drupal\jsonapi\Routing\Routes::rebuild();
    // @todo Determine why flush all caches is needed for JSON:API routes.
    drupal_flush_all_caches();
  }

  /**
   * Test Schema.org JSON:API support.
   */
  public function testJsonApi() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/jsonapi/node/event/' . $this->node->uuid());

    // Check subtype.
    $assert_session->responseContains('"subtype":');
    $assert_session->responseNotContains('"schema_subtype":');

    // Check properties.
    $assert_session->responseContains('"alternateName":"Another event"');
    $assert_session->responseNotContains('"schema_alternate_name":"Another event"');
  }

}
