<?php

namespace Drupal\Tests\schemadotorg_rdf\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests for Schema.org RDF.
 *
 * @group schemadotorg
 */
class SchemaDotOrgRdfTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_rdf'];

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

    // Create Thing node with field.
    $this->drupalCreateContentType([
      'type' => 'thing',
      'name' => 'Thing',
    ]);
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'schema_alternate_name',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'thing',
      'field_name' => 'schema_alternate_name',
    ])->save();
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'thing')
      ->setComponent('schema_alternate_name')->save();

    // Create Thing with mapping.
    $node_mapping = SchemaDotOrgMapping::create([
      'targetEntityType' => 'node',
      'bundle' => 'thing',
      'type' => 'Thing',
      'properties' => [
        'title' => ['property' => 'name'],
        'schema_alternate_name' => ['property' => 'alternateName'],
      ],
    ]);
    $node_mapping->save();
    $this->nodeMapping = $node_mapping;

    // Create a node.
    $this->node = $this->drupalCreateNode([
      'type' => 'thing',
      'title' => 'A thing',
      'schema_alternate_name' => ['value' => 'Another thing'],
    ]);
  }

  /**
   * Test Schema.org RDF(a) support.
   */
  public function testRdf() {
    // Check that the Schema.org mapping is sync'd with the RDF mapping.
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertSession()->responseContains('<span property="schema:name">A thing</span>');
    $this->assertSession()->responseContains('<span property="schema:name" content="A thing" class="hidden"></span>');
    $this->assertSession()->responseContains('<div property="schema:alternateName">Another thing</div>');

    // Delete the Schema.org mapping.
    $this->nodeMapping->delete();
    // @todo Determine why the deleted RDF mapping is not clearing the page cache.
    drupal_flush_all_caches();

    // Check that the RDF mapping is removed when Schema.org mapping is deleted.
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertSession()->responseNotContains('<span property="schema:name">A thing</span>');
    $this->assertSession()->responseNotContains('<span property="schema:name" content="A thing" class="hidden"></span>');
    $this->assertSession()->responseNotContains('<div property="schema:alternateName">Another thing</div>');
  }

}
