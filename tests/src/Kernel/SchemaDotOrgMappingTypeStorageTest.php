<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Tests the Schema.org type manager service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage
 * @group schemadotorg
 */
class SchemaDotOrgMappingTypeStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['schemadotorg', 'paragraphs', 'file'];

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

    // Install the Schema.org mapping entity.
    $this->installEntitySchema('schemadotorg_mapping_type');

    // Install the Schema.org configuration settings.
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
    $common_schema_types = $this->storage->getCommonSchemaTypes('node');
    $this->assertEquals('Common', $common_schema_types['common']['label']);
    $this->assertEquals('Thing', $common_schema_types['common']['types'][0]);

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
    $this->assertEqual($expected_base_field_names, $actual_base_field_names);

    // Check getting default Schema.org properties.
    $property_defaults = $this->storage->getSchemaPropertyDefaults('node');
    $this->assertTrue(in_array('additionalName', $property_defaults));

    // Check getting default Schema.org unlimited properties.
    $expected_property_unlimited = [
      'audience' => 'audience',
      'address' => 'address',
      'about' => 'about',
      'affiliation' => 'affiliation',
      'alumniOf' => 'alumniOf',
      'attendee' => 'attendee',
      'award' => 'award',
      'contentLocation' => 'contentLocation',
      'contactPoint' => 'contactPoint',
      'knowsLanguage' => 'knowsLanguage',
      'keywords' => 'keywords',
      'mentions' => 'mentions',
      'photo' => 'photo',
      'sponsor' => 'sponsor',
      'suitableForDiet' => 'suitableForDiet',
      'thumbnailUrl' => 'thumbnailUrl',
      'worksFor' => 'worksFor',
      'video' => 'video',
    ];
    $actual_property_unlimited = $this->storage->getSchemaPropertyUnlimited('user');
    $this->assertEquals($expected_property_unlimited, $actual_property_unlimited);
  }

}
