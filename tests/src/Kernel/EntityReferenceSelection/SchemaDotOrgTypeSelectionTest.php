<?php

namespace Drupal\Tests\schemadotorg\Kernel\EntityReferenceSelection;

use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatch;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;
use Symfony\Component\Routing\Route;

/**
 * Tests selecting entities using the field's mapping Schema.org type.
 *
 * @group schemadotorg
 * @see \Drupal\Tests\system\Kernel\Entity\EntityReferenceSelectionReferenceableTest
 */
class SchemaDotOrgTypeSelectionTest extends SchemaDotOrgKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'node', 'taxonomy', 'field', 'text'];

  /**
   * A Schema.org mapping entity for a node.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   */
  protected $nodeMapping;

  /**
   * The field configuration.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $fieldConfig;

  /**
   * The selection handler.
   *
   * @var \Drupal\schemadotorg\Plugin\EntityReferenceSelection\SchemaDotOrgEnumerationSelection
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->installer->install();

    // Create Persons node with a subtype.
    NodeType::create([
      'type' => 'person',
      'name' => 'Person',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'schema_type',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'person',
      'field_name' => 'schema_type',
      'settings' => [
        'handler' => 'schemadotorg_type',
        'handler_settings' => [
          'target_type' => 'taxonomy_term',
          'depth' => 1,
          'schemadotorg_mapping' => [
            'entity_type' => 'node',
            'bundle' => 'person',
            'field_name' => 'schema_type',
          ],
        ],
      ],
    ])->save();
    $node_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'person',
      'type' => 'Person',
      'subtype' => TRUE,
      'properties' => [],
    ]);
    $node_mapping->save();
    $this->nodeMapping = $node_mapping;

    $this->fieldConfig = FieldConfig::loadByName('node', 'person', 'schema_type');
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldConfig);
  }

  /**
   * Tests type selection.
   */
  public function testTypeSelection() {
    $form = [];
    $form_state = new FormState();

    // Check referenceable entities.
    $referenceable_entities = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals(['Patient'], array_values($referenceable_entities['schema_thing']));

    // Check displaying message about the Schema.org subtypes.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>Subtypes will automatically available based the Schema.org type (Person).</p>", $form['message']['#markup']);

    // Change the schema type to patient which has not subtypes.
    $this->nodeMapping->setSchemaType('Patient')->save();
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldConfig);

    // Check displaying message when the Schema.org type has no subtypes.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>The Schema.org type (Patient) has no subtypes.</p>", $form['message']['#markup']);

    // Delete the mapping.
    $this->nodeMapping->delete();
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldConfig);

    // Check displaying message when the field's entity is not mapped to a Schema.org type.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>This field's entity is not mapped to a Schema.org type.</p>", $form['message']['#markup']);
  }

}
