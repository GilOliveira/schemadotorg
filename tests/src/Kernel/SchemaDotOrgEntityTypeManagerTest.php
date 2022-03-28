<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Tests the Schema.org type manager service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager
 * @group schemadotorg
 */
class SchemaDotOrgEntityTypeManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['schemadotorg', 'paragraphs', 'file'];

  /**
   * The Schema.org entity type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface
   */
  protected $schemaEntityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install the Schema.org mapping entity.
    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('paragraphs_type');

    // Install the Schema.org configuration settings.
    $this->installConfig(['schemadotorg']);
    // Install the Schema.org type and properties tables.
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    // Set entity type manager.
    $this->schemaEntityTypeManager = $this->container->get('schemadotorg.entity_type_manager');
  }

  /**
   * Test Schema.org entity type manager .
   */
  public function testEntityTypeManager() {
    // Check getting entity types that implement Schema.org.
    $expected_entity_types = [
      'block_content' => 'block_content',
      'media' => 'media',
      'node' => 'node',
      'paragraph' => 'paragraph',
      'user' => 'user',
    ];
    $actual_entity_types = $this->schemaEntityTypeManager->getEntityTypes();
    $this->assertEquals($expected_entity_types, $actual_entity_types);

    // Check getting default bundle for an entity type and Schema.org type.
    $tests = [
      ['', '', ''],
      ['not', 'Person', ''],
      ['user', 'Person', 'user'],
      ['media', 'AudioObject', 'audio'],
    ];
    foreach ($tests as $test) {
      $this->assertEquals($test[2], $this->schemaEntityTypeManager->getDefaultSchemaTypeBundle($test[0], $test[1]));
    }

    // Check getting default Schema.org type for an entity type and bundle.
    $tests = [
      ['', '', ''],
      ['not', 'user', ''],
      ['user', 'user', 'Person'],
      ['media', 'audio', 'AudioObject'],
    ];
    foreach ($tests as $test) {
      $this->assertEquals($test[2], $this->schemaEntityTypeManager->getDefaultSchemaType($test[0], $test[1]));
    }

    // Check getting common Schema.org types for a specific entity type.
    $common_schema_types = $this->schemaEntityTypeManager->getCommonSchemaTypes('node');
    $this->assertEquals('Common', $common_schema_types['common']['label']);
    $this->assertEquals('Thing', $common_schema_types['common']['types'][0]);

    // Check getting an entity type's base field mappings.
    $expected_base_field_mappings = [
      'email' => 'mail',
      'image' => 'user_picture',
    ];
    $actual_base_field_mappings = $this->schemaEntityTypeManager->getBaseFieldMappings('user');
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
    $actual_base_field_names = $this->schemaEntityTypeManager->getBaseFieldNames('block_content');
    $this->assertEqual($expected_base_field_names, $actual_base_field_names);

    // Check getting default Schema.org properties.
    $property_defaults = $this->schemaEntityTypeManager->getSchemaPropertyDefaults('node');
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
    $actual_property_unlimited = $this->schemaEntityTypeManager->getSchemaPropertyUnlimited('user');
    $this->assertEquals($expected_property_unlimited, $actual_property_unlimited);

    // Create contact point paragraph and Schema.org mapping.
    ParagraphsType::create([
      'id' => 'contact_point',
      'label' => 'Contact Point',
    ])->save();
    SchemaDotOrgMapping::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'contact_point',
      'type' => 'ContactPoint',
    ])->save();

    // Check getting field types for Schema.org property.
    $tests = [
      [
        'name',
        [
          'string' => 'string',
          'string_long' => 'string_long',
          'list_string' => 'list_string',
          'text' => 'text',
          'text_long' => 'text_long',
          'text_with_summary' => 'text_with_summary',
        ],
      ],
      [
        'gender',
        [
          'field_ui:entity_reference:taxonomy_term' => 'field_ui:entity_reference:taxonomy_term',
        ],
      ],
      [
        'worksFor',
        [
          'field_ui:entity_reference:node' => 'field_ui:entity_reference:node',
          'string' => 'string',
        ],
      ],
      [
        'contactPoint',
        [
          'field_ui:entity_reference_revisions:paragraph' => 'field_ui:entity_reference_revisions:paragraph',
          'string' => 'string',
        ],
      ],

    ];
    foreach ($tests as $test) {
      $this->assertEquals($test[1], $this->schemaEntityTypeManager->getSchemaPropertyFieldTypes($test[0]));
    }
  }

}
