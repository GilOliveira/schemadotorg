<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_rdf\Functional;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Tests for Schema.org RDF.
 *
 * @group schemadotorg
 */
class SchemaDotOrgRdfTest extends SchemaDotOrgBrowserTestBase {
  use SchemaDotOrgTestSubtypeTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'schemadotorg_subtype',
    'schemadotorg_rdf',
  ];

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
  protected function setUp(): void {
    parent::setUp();

    // Create Event node with field.
    $this->drupalCreateContentType([
      'type' => 'event',
      'name' => 'Event',
    ]);
    $this->createSchemaDotOrgField('node', 'Event');
    $this->createSchemaDotOrgSubTypeField('node', 'Event');
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'event')
      ->setComponent('schema_alternate_name')
      ->setComponent('schema_event_subtype')->save();

    // Create Event with mapping.
    $node_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'event',
      'schema_type' => 'Event',
      'schema_properties' => [
        'title' => 'name',
        'schema_alternate_name' => 'alternateName',
        'schema_event_subtype' => 'subtype',
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
  }

  /**
   * Test Schema.org RDF(a) support.
   */
  public function testRdf(): void {
    // Check that the Schema.org mapping is sync'd with the RDF mapping.
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertSession()->responseContains('typeof="schema:Event"');
    $this->assertSession()->responseContains('<span property="schema:name">A event</span>');
    $this->assertSession()->responseContains('<span property="schema:name" content="A event" class="hidden"></span>');
    $this->assertSession()->responseContains('<div property="schema:alternateName">Another event</div>');

    // Set the subtype.
    $this->node->schema_event_subtype->value = 'BusinessEvent';
    $this->node->save();

    // Check replacing the RDF Schema.org type with the Schema.org subtype.
    // @see schemadotorg_rdf_preprocess_node
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertSession()->responseNotContains('typeof="schema:Event"');
    $this->assertSession()->responseContains('typeof="schema:BusinessEvent"');

    // Delete the Schema.org mapping.
    $this->nodeMapping->delete();
    // @todo Determine why the deleted RDF mapping is not clearing the page cache.
    drupal_flush_all_caches();

    // Check that the RDF mapping is removed when Schema.org mapping is deleted.
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertSession()->responseNotContains('<span property="schema:name">A event</span>');
    $this->assertSession()->responseNotContains('<span property="schema:name" content="A event" class="hidden"></span>');
    $this->assertSession()->responseNotContains('<div property="schema:alternateName">Another event</div>');
  }

}
