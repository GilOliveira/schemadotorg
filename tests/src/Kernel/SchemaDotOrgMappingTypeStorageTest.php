<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;

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
  protected static $modules = ['paragraphs', 'file'];

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
      ['', '', []],
      ['not', 'Person', []],
      ['user', 'Person', ['user' => 'user']],
      ['media', 'AudioObject', ['audio' => 'audio']],
    ];
    foreach ($tests as $test) {
      $this->assertEquals($test[2], $this->storage->getDefaultSchemaTypeBundles($test[0], $test[1]));
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

    // Check getting default field groups for a specific entity type.
    $expected_default_field_group = [
      'label' => 'General',
      'properties' => [
        'name',
        'headline',
        'alternativeHeadline',
        'description',
        'articleBody',
        'text',
        'author',
      ],
    ];
    $actual_default_field_groups = $this->storage->getDefaultFieldGroups('node');
    $this->assertEquals($expected_default_field_group, $actual_default_field_groups['general']);

    // Check getting default field group format type.
    $values = [
      'targetEntityType' => 'paragraph',
      'bundle' => 'some_bundle',
      'mode' => 'default',
    ];
    $this->assertEquals('details', $this->storage->getDefaultFieldGroupFormatType('node', EntityFormDisplay::create($values)));
    $this->assertEquals('fieldset', $this->storage->getDefaultFieldGroupFormatType('node', EntityViewDisplay::create($values)));

    // Check getting default field group format settings.
    $this->assertEquals(['open' => TRUE], $this->storage->getDefaultFieldGroupFormatSettings('node', EntityFormDisplay::create($values)));
    $this->assertEquals([], $this->storage->getDefaultFieldGroupFormatSettings('node', EntityViewDisplay::create($values)));

    // Check getting common Schema.org types for a specific entity type.
    $recommended_schema_types = $this->storage->getRecommendedSchemaTypes('node');
    $this->assertEquals('Common', $recommended_schema_types['common']['label']);
    $this->assertEquals('Person', $recommended_schema_types['common']['types'][0]);

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

    // Check getting entity type bundles. (i.e node).
    $actual_entity_type_bundles = $this->storage->getEntityTypeBundles();
    $this->assertArrayHasKey('paragraph', $actual_entity_type_bundles);
    $this->assertInstanceOf(ContentEntityType::class, $actual_entity_type_bundles['paragraph']);

    // Check getting entity type bundle definitions. (i.e node_type).
    $actual_entity_type_bundle_definitions = $this->storage->getEntityTypeBundleDefinitions();
    $this->assertArrayHasKey('paragraph', $actual_entity_type_bundle_definitions);
    $this->assertInstanceOf(ConfigEntityType::class, $actual_entity_type_bundle_definitions['paragraph']);
  }

}
