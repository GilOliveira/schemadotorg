<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests the Schema.org mapping entity.
 *
 * @coversClass \Drupal\schemadotorg\Entity\SchemaDotOrgMappingType
 * @group schemadotorg
 */
class SchemaDotOrgMappingTypeEntityTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'image',
    'user',
    'node',
    'media',
    'paragraphs',
    'schemadotorg_paragraphs',
  ];

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

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('media');
    $this->installEntitySchema('paragraph');

    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg', 'schemadotorg_paragraphs']);

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    // Set Schema.org mapping type storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');
  }

  /**
   * Test Schema.org mapping entity.
   */
  public function testSchemaDotOrgMappingTypeEntity() {
    $user_mapping_type = $this->storage->load('user');
    $node_mapping_type = $this->storage->load('node');
    $media_mapping_type = $this->storage->load('media');
    $paragaph_mapping_type = $this->storage->load('paragraph');

    // Check getting the mapping type label.
    $this->assertEquals('User', $user_mapping_type->label());
    $this->assertEquals('Content', $node_mapping_type->label());
    $this->assertEquals('Paragraph', $paragaph_mapping_type->label());
    $this->assertEquals('Media', $media_mapping_type->label());

    // Check getting default bundle for a Schema.org type.
    $tests = [
      ['user', 'Person', ['user' => 'user']],
      ['media', 'AudioObject', ['audio' => 'audio']],
    ];
    foreach ($tests as $test) {
      $mapping_type = $this->storage->load($test[0]);
      $this->assertEquals($test[2], $mapping_type->getDefaultSchemaTypeBundles($test[1]));
    }

    // Check getting default Schema.org type for a bundle.
    $tests = [
      ['user', 'user', 'Person'],
      ['media', 'audio', 'AudioObject'],
    ];
    foreach ($tests as $test) {
      $mapping_type = $this->storage->load($test[0]);
      $this->assertEquals($test[2], $mapping_type->getDefaultSchemaType($test[1]));
    }

    // Check getting default Schema.org type's default properties.
    $expected_default_type_properties = [
      'dateCreated' => 'dateCreated',
      'dateModified' => 'dateModified',
      'image' => 'image',
      'name' => 'name',
      'thumbnail' => 'thumbnail',
      'inLanguage' => 'inLanguage',
    ];
    $actual_default_type_properties = $media_mapping_type->getDefaultSchemaTypeProperties('ImageObject');
    $this->assertEquals($expected_default_type_properties, $actual_default_type_properties);
    $this->assertArrayHasKey('step', $media_mapping_type->getDefaultSchemaTypeProperties('HowTo'));
    $this->assertArrayNotHasKey('step', $media_mapping_type->getDefaultSchemaTypeProperties('Recipe'));

    // Check getting default Schema.org type's subtypes.
    $actual_default_type_subtypes = $node_mapping_type->getDefaultSchemaTypeSubtypes();
    $this->assertTrue(in_array('Event', $actual_default_type_subtypes));

    // Check getting default field weights.
    $default_field_weights = $paragaph_mapping_type->getDefaultFieldWeights();
    $this->assertEquals(1, $default_field_weights['name']);
    $this->assertEquals(2, $default_field_weights['title']);
    $this->assertEquals(3, $default_field_weights['alternateName']);
    $this->assertEquals(4, $default_field_weights['description']);

    // Check getting default field groups.
    $expected_default_field_group = [
      'label' => 'General information',
      'properties' => [
        'type',
        'title',
        'name',
        'headline',
        'alternativeHeadline',
        'description',
        'about',
        'articleBody',
        'text',
        'author',
      ],
    ];
    $actual_default_field_groups = $node_mapping_type->getDefaultFieldGroups();
    $this->assertEquals($expected_default_field_group, $actual_default_field_groups['general']);

    // Check getting default field group label suffix.
    $this->assertEquals('information', $node_mapping_type->getDefaultFieldGroupLabelSuffix());

    // Check getting default field group format type.
    $values = [
      'targetEntityType' => 'paragraph',
      'bundle' => 'some_bundle',
      'mode' => 'default',
    ];
    $this->assertEquals('details', $node_mapping_type->getDefaultFieldGroupFormatType(EntityFormDisplay::create($values)));
    $this->assertEquals('fieldset', $node_mapping_type->getDefaultFieldGroupFormatType(EntityViewDisplay::create($values)));

    // Check getting default field group format settings.
    $this->assertEquals(['open' => TRUE], $node_mapping_type->getDefaultFieldGroupFormatSettings(EntityFormDisplay::create($values)));
    $this->assertEquals([], $node_mapping_type->getDefaultFieldGroupFormatSettings(EntityViewDisplay::create($values)));

    // Check getting common Schema.org types.
    $recommended_schema_types = $node_mapping_type->getRecommendedSchemaTypes();
    $this->assertEquals('Common', $recommended_schema_types['common']['label']);
    $this->assertEquals('Place', $recommended_schema_types['common']['types'][0]);

    // Check getting an entity type's base field mappings.
    $expected_base_field_mappings = [
      'email' => ['mail' => 'mail'],
      'name' => ['name' => 'name'],
      'image' => ['user_picture' => 'user_picture'],
      'inLanguage' => ['langcode' => 'langcode'],
    ];
    $actual_base_field_mappings = $user_mapping_type->getBaseFieldMappings();
    $this->assertEquals($expected_base_field_mappings, $actual_base_field_mappings);

    // Check getting an entity type's base fields names.
    $expected_base_field_names = [
      'uuid' => 'uuid',
      'revision_created' => 'revision_created',
      'revision_user' => 'revision_user',
      'uid' => 'uid',
      'name' => 'name',
      'thumbnail' => 'thumbnail',
      'created' => 'created',
      'changed' => 'changed',
      'path' => 'path',
      'langcode' => 'langcode',
      'field_media_audio_file' => 'field_media_audio_file',
      'field_media_document' => 'field_media_document',
      'field_media_image' => 'field_media_image',
      'field_media_oembed_video' => 'field_media_oembed_video',
      'field_media_video_file' => 'field_media_video_file',
    ];
    $actual_base_field_names = $media_mapping_type->getBaseFieldNames();
    $this->assertEquals($expected_base_field_names, $actual_base_field_names);
  }

}
