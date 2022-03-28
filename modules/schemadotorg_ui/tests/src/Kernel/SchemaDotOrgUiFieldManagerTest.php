<?php

namespace Drupal\Tests\schemadotorg_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Tests the Schema.org type manager service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager
 * @group schemadotorg
 */
class SchemaDotOrgUiFieldManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['schemadotorg', 'schemadotorg_ui', 'paragraphs', 'file'];

  /**
   * The Schema.org UI field manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface
   */
  protected $fieldManager;

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

    // Set Schema.org UI field manager.
    $this->fieldManager = $this->container->get('schemadotorg_ui.field_manager');
  }

  /**
   * Test Schema.org UI field manager.
   */
  public function testFieldManager() {
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
      $this->assertEquals($test[1], $this->fieldManager->getSchemaPropertyFieldTypes($test[0]));
    }
  }

}
