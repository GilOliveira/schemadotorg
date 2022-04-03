<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Tests the Schema.org mapping storage.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgMappingStorage
 * @group schemadotorg
 */
class SchemaDotOrgMappingStorageTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'node'];

  /**
   * The Schema.org mapping storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    // Set Schema.org mapping storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');

    // Create Thing and Image node with mappings.
    NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ])->save();
    NodeType::create([
      'type' => 'image_object',
      'name' => 'ImageObject',
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'type' => 'Thing',
      'properties' => [
        'title' => ['property' => 'name'],
        'image' => ['property' => 'image'],
      ],
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'image_object',
      'type' => 'ImageObject',
      'properties' => [
        'title' => ['property' => 'name'],
      ],
    ])->save();
  }

  /**
   * Test Schema.org mapping storage.
   */
  public function testSchemaDotOrgMappingStorage() {
    // Check determining if an entity type and bundle are mapped to Schema.org.
    $this->assertFalse($this->storage->isBundleMapped('node', 'page'));
    $this->assertTrue($this->storage->isBundleMapped('node', 'thing'));

    // Check getting the Schema.org property name for an entity field mapping.
    $this->assertEquals('name', $this->storage->getSchemaPropertyName('node', 'thing', 'title'));
    $this->assertNull($this->storage->getSchemaPropertyName('node', 'thing', 'not_field'));
    $this->assertNull($this->storage->getSchemaPropertyName('node', 'not_thing', 'thing'));

    // Check getting the Schema.org property's range includes Schema.org types.
    $this->assertEquals(['Text' => 'Text'], $this->storage->getSchemaPropertyRangeIncludes('node', 'thing', 'title'));
    $this->assertEquals(['ImageObject' => 'ImageObject', 'URL' => 'URL'], $this->storage->getSchemaPropertyRangeIncludes('node', 'thing', 'image'));
    $this->assertEquals([], $this->storage->getSchemaPropertyRangeIncludes('node', 'thing', 'not_title'));
    $this->assertEquals([], $this->storage->getSchemaPropertyRangeIncludes('node', 'not_thing', 'title'));

    // Check getting the Schema.org property target mappings.
    $schemadotorg_mappings = $this->storage->getSchemaPropertyTargetMappings('node', 'thing', 'image', 'node');
    $schemadotorg_mapping = reset($schemadotorg_mappings);
    $this->assertEquals('node.image_object', $schemadotorg_mapping->id());
    $schemadotorg_mappings = $this->storage->getSchemaPropertyTargetMappings('node', 'thing', 'image', 'paragraphs');
    $this->assertEmpty($schemadotorg_mappings);

    // Check getting the Schema.org property target bundles.
    $this->assertEquals(['image_object' => 'image_object'], $this->storage->getSchemaPropertyTargetBundles('node', 'thing', 'image', 'node'));
    $this->assertEquals([], $this->storage->getSchemaPropertyTargetBundles('node', 'thing', 'image', 'paragraph'));

    // Check determining if Schema.org type is mapped to an entity.
    $this->assertTrue($this->storage->isSchemaTypeMapped('node', 'Thing'));
    $this->assertFalse($this->storage->isSchemaTypeMapped('node', 'NotThing'));
    $this->assertFalse($this->storage->isSchemaTypeMapped('not_node', 'Thing'));

    // Check loading by target entity id and Schema.org type.
    $this->assertEquals('node.thing', $this->storage->loadBySchemaType('node', 'Thing')->id());
    $this->assertNull($this->storage->loadBySchemaType('node', 'NotThing'));
  }

}
