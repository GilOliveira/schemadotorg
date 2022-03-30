<?php

namespace Drupal\Tests\schemadotorg\Kernel;

/**
 * Tests the Schema.org type manager service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage
 * @group schemadotorg
 */
class SchemaDotOrgMappingTypeStorageTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg', 'paragraphs', 'file'];

  /**
   * The Schema.org mapping type storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installConfig(['schemadotorg']);

    // Set Schema.org mapping storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');
  }

  /**
   * Test Schema.org mapping type storage.
   */
  public function testSchemaDotOrgMappingTypeStorage() {
    // Check getting entity types that implement Schema.org.
    $expected_entity_types = [
      'block_content' => 'block_content',
      'media' => 'media',
      'node' => 'node',
      'paragraph' => 'paragraph',
      'user' => 'user',
    ];
    $actual_entity_types = $this->storage->getEntityTypes();
    $this->assertEquals($expected_entity_types, $actual_entity_types);

    // Check getting default bundle for an entity type and Schema.org type.
    $tests = [
      ['', '', ''],
      ['not', 'Person', ''],
      ['user', 'Person', 'user'],
      ['media', 'AudioObject', 'audio'],
    ];
    foreach ($tests as $test) {
      $this->assertEquals($test[2], $this->storage->getDefaultSchemaTypeBundle($test[0], $test[1]));
    }

    // Check getting default Schema.org type for an entity type and bundle.
    $tests = [
      ['', '', ''],
      ['not', 'user', ''],
      ['user', 'user', 'Person'],
      ['media', 'audio', 'AudioObject'],
    ];
    foreach ($tests as $test) {
      $this->assertEquals($test[2], $this->storage->getDefaultSchemaType($test[0], $test[1]));
    }

    // Check getting common Schema.org types for a specific entity type.
    $recommended_schema_types = $this->storage->getRecommendedSchemaTypes('node');
    $this->assertEquals('Common', $recommended_schema_types['common']['label']);
    $this->assertEquals('Thing', $recommended_schema_types['common']['types'][0]);

    // Check getting an entity type's base field mappings.
    $expected_base_field_mappings = [
      'email' => 'mail',
      'image' => 'user_picture',
    ];
    $actual_base_field_mappings = $this->storage->getBaseFieldMappings('user');
    $this->assertEquals($expected_base_field_mappings, $actual_base_field_mappings);

    // Check getting an entity type's base fields names.
    $expected_base_field_names = [
      'uuid' => 'uuid',
      'type' => 'type',
      'info' => 'info',
      'revision_created' => 'revision_created',
      'revision_user' => 'revision_user',
      'changed' => 'changed',
    ];
    $actual_base_field_names = $this->storage->getBaseFieldNames('block_content');
    $this->assertEquals($expected_base_field_names, $actual_base_field_names);
  }

}
