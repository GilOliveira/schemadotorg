<?php

namespace Drupal\Tests\schemadotorg\Kernel\EntityReferenceSelection;

use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;

/**
 * Tests selecting entities using the field's mapping Schema.org property.
 *
 * @group schemadotorg
 * @see \Drupal\Tests\system\Kernel\Entity\EntityReferenceSelectionReferenceableTest
 */
class SchemaDotOrgRangeIncludesSelectionTest extends SchemaDotOrgKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'node', 'field', 'text'];

  /**
   * The field configuration.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $fieldConfig;

  /**
   * The selection handler.
   *
   * @var \Drupal\schemadotorg\Plugin\EntityReferenceSelection\SchemaDotOrgRangeIncludesSelection
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    // Create Thing node with field.
    NodeType::create([
      'type' => 'person',
      'name' => 'Person',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'schema_works_for',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'person',
      'field_name' => 'schema_works_for',
      'settings' => [
        'handler' => 'schemadotorg_range_includes',
        'handler_settings' => [
          'target_type' => 'node',
          'schemadotorg_mapping' => [
            'entity_type' => 'node',
            'bundle' => 'person',
            'field_name' => 'schema_works_for',
          ],
        ],
      ],
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'person',
      'type' => 'Person',
      'properties' => [
        'schema_works_for' => 'worksFor',
      ],
    ])->save();

    NodeType::create([
      'type' => 'organization',
      'name' => 'Organization',
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'organization',
      'type' => 'Organization',
    ])->save();

    NodeType::create([
      'type' => 'educational_organization',
      'name' => 'Educational Organization',
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'educational_organization',
      'type' => 'EducationalOrganization',
    ])->save();

    Node::create(['type' => 'organization', 'title' => 'Organization node'])->save();
    Node::create(['type' => 'educational_organization', 'title' => 'Educational organization node'])->save();

    $this->fieldConfig = FieldConfig::loadByName('node', 'person', 'schema_works_for');
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldConfig);
  }

  /**
   * Tests range includes selection.
   */
  public function testEnumerationSelection() {
    $form = [];
    $form_state = new FormState();

    // Check referenceable entities.
    $expected_referenceable_entities = [
      'organization' => [1 => 'Organization node'],
      'educational_organization' => [2 => 'Educational organization node'],
    ];
    $actual_referenceable_entities = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals($expected_referenceable_entities, $actual_referenceable_entities);

    // Check displaying message about entity reference selection.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>The below content types will be automatically be available based this field's associated Schema.org property (worksFor).</p>", $form['message']['#markup']);

    // Delete Organization and Educational Organization node types.
    $node_types = NodeType::loadMultiple(['organization', 'educational_organization']);
    foreach ($node_types as $node_type) {
      $node_type->delete();
    }

    // Check displaying message about broken entity reference selection.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>There are no content types that will be automatically available based this field's associated Schema.org property.</p><p><strong>Please create a new content type and map it to one of the following Schema.org types (Organization).</strong></p>", $form['message']['#markup']);

    // Delete the node:Person Schema.org mapping.
    SchemaDotOrgMapping::load('node.person')->delete();

    // Check displaying message that the field is not mapped.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>This field is not mapped to a Schema.org property.</p><p><strong>Please update this content type's Schema.org type mapping.</strong></p>", $form['message']['#markup']);
  }

}
