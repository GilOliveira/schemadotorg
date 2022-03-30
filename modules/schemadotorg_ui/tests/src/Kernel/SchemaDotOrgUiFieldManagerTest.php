<?php

namespace Drupal\Tests\schemadotorg_ui\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;

/**
 * Tests the Schema.org type manager service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager
 * @group schemadotorg
 */
class SchemaDotOrgUiFieldManagerTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'node', 'paragraphs', 'file', 'field', 'schemadotorg', 'schemadotorg_ui'];

  /**
   * The Schema.org UI field manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface
   */
  protected $fieldManager;

  /**
   * A node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install the Schema.org mapping entity.
    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
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

    // Create Thing node with field.
    $node_type = NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ]);
    $node_type->save();
    $this->nodeType = $node_type;
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'schema_alternate_name',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'thing',
      'field_name' => 'schema_alternate_name',
      'label' => 'Alternate name',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'schema_identifier',
      'type' => 'string',
    ])->save();

    // Create contact point paragraph and Schema.org mapping.
    ParagraphsType::create([
      'id' => 'contact_point',
      'label' => 'Contact Point',
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'paragraph',
      'target_bundle' => 'contact_point',
      'type' => 'ContactPoint',
    ])->save();

    // Set Schema.org UI field manager.
    $this->fieldManager = $this->container->get('schemadotorg_ui.field_manager');
  }

  /**
   * Test Schema.org UI field manager.
   */
  public function testFieldManager() {
    // Check determining if a field exists.
    $this->assertTrue($this->fieldManager->fieldExists('node', 'thing', 'schema_alternate_name'));
    $this->assertFalse($this->fieldManager->fieldExists('node', 'thing', 'not_schema_alternate_name'));
    $this->assertFalse($this->fieldManager->fieldExists('node', 'not_thing', 'schema_alternate_name'));
    $this->assertFalse($this->fieldManager->fieldExists('not_node', 'thing', 'schema_alternate_name'));
    $this->assertFalse($this->fieldManager->fieldExists('node', 'thing', 'schema_identifier'));

    // Check determining if a field storage exists.
    $this->assertTrue($this->fieldManager->fieldStorageExists('node', 'schema_alternate_name'));
    $this->assertFalse($this->fieldManager->fieldStorageExists('node', 'not_schema_alternate_name'));
    $this->assertFalse($this->fieldManager->fieldStorageExists('not_node', 'schema_alternate_name'));
    $this->assertTrue($this->fieldManager->fieldStorageExists('node', 'schema_identifier'));

    // Check getting a field's label from an existing field instance.
    $this->assertEquals('Alternate name', $this->fieldManager->getFieldLabel('node', 'schema_alternate_name'));
    $this->assertNull($this->fieldManager->getFieldLabel('node', 'not_schema_alternate_name'));
    $this->assertNull($this->fieldManager->getFieldLabel('not_node', 'schema_alternate_name'));
    $this->assertNull($this->fieldManager->getFieldLabel('node', 'schema_identifier'));

    // Check getting a Schema.org property's available field types as options.
    $expected_field_type_options = [
      'Recommended' => [
        'string' => 'Text (plain)',
        'string_long' => 'Text (plain, long)',
      ],
      'General' => [
        'boolean' => 'Boolean',
        'email' => 'Email',
        'timestamp' => 'Timestamp',
      ],
      'Number' => [
        'decimal' => 'Number (decimal)',
        'float' => 'Number (float)',
        'integer' => 'Number (integer)',
      ],
      'Reference' => [
        'field_ui:entity_reference:node' => 'Content',
        'file' => 'File',
        'entity_reference' => 'Entity reference',
        'field_ui:entity_reference:user' => 'User',
      ],
    ];
    $actual_field_type_options = $this->fieldManager->getPropertyFieldTypeOptions('alternateName');
    $this->convertMarkupToStrings($actual_field_type_options);
    $this->assertEquals($expected_field_type_options, $actual_field_type_options);

    // Check getting available fields as options.
    $expected_field_options = [
      '_add_' => 'Add a new field…',
      'Fields' => [
        'schema_alternate_name' => 'Alternate name (Text (plain))',
      ],
      'Base fields' => [
        'uuid' => 'UUID (UUID)',
        'revision_uid' => 'Revision user (Entity reference)',
        'uid' => 'Authored by (Entity reference)',
        'title' => 'Title (Text (plain))',
        'created' => 'Authored on (Created)',
        'changed' => 'Changed (Last changed)',
        'promote' => 'Promoted to front page (Boolean)',
        'sticky' => 'Sticky at top of lists (Boolean)',
      ],
      'Existing fields' => [
        'schema_identifier' => 'schema_identifier (Text (plain))',
      ],
    ];
    $actual_field_options = $this->fieldManager->getFieldOptions('node', 'thing');
    $this->convertMarkupToStrings($actual_field_options);
    $this->assertEquals($expected_field_options, $actual_field_options);

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
      $this->assertEquals($test[1], $this->fieldManager->getSchemaPropertyFieldTypes($test[0]));
    }
  }

}