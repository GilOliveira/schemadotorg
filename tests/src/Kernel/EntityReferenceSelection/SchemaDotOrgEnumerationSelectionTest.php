<?php

namespace Drupal\Tests\schemadotorg\Kernel\EntityReferenceSelection;

use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatch;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;
use Symfony\Component\Routing\Route;

/**
 * Tests selecting entities using the field's mapping Schema.org property.
 *
 * @group schemadotorg
 * @see \Drupal\Tests\system\Kernel\Entity\EntityReferenceSelectionReferenceableTest
 */
class SchemaDotOrgEnumerationSelectionTest extends SchemaDotOrgKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'field', 'text', 'taxonomy'];

  /**
   * A Schema.org mapping entity for a user.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   */
  protected $userMapping;

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
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->installer->install();

    // Create user with Person mapping and gender enumeration property/field.
    $user_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'user',
      'target_bundle' => 'user',
      'type' => 'Person',
      'properties' => [
        'schema_gender' => 'gender',
      ],
    ]);
    $user_mapping->save();
    $this->userMapping = $user_mapping;
    FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => 'schema_gender',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
    ])->save();
    $field_config = FieldConfig::create([
      'entity_type' => 'user',
      'bundle' => 'user',
      'field_name' => 'schema_gender',
      'settings' => [
        'handler' => 'schemadotorg_enumeration',
        'handler_settings' => [
          'target_type' => 'taxonomy_term',
          'schemadotorg_mapping' => [
            'entity_type' => 'user',
            'bundle' => 'user',
            'field_name' => 'schema_gender',
          ],
        ],
      ],
    ]);
    $field_config->save();
    $this->fieldConfig = $field_config;
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldConfig);
  }

  /**
   * Tests enumeration selection.
   */
  public function testEnumerationSelection() {
    $form = [];
    $form_state = new FormState();

    // Check referenceable entities.
    $referenceable_entities = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals(['Female', 'Male'], array_values($referenceable_entities['schema_enumeration']));

    // Unpublish the 'Male' term.
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $entity_ids = $term_storage->getQuery()
      ->condition('vid', 'schema_enumeration')
      ->condition('schema_type', 'Male')
      ->execute();
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $term_storage->load(reset($entity_ids));
    $term->setUnpublished()->save();

    // Check published referenceable entities.
    $referenceable_entities = $this->selectionHandler->getReferenceableEntities();
    $this->assertNotEquals(['Female', 'Male'], array_values($referenceable_entities['schema_enumeration']));
    $this->assertEquals(['Female'], array_values($referenceable_entities['schema_enumeration']));

    // Check displaying message when the enumeration is found.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>Taxonomy terms (Gender Type) will be automatically available based this field's associated Schema.org property enumeration.</p>", $form['message']['#markup']);

    // Remove the entity_type_id, bundle, and schema type from the handler settings.
    $this->fieldConfig->setSetting('handler_settings', ['target_type' => 'taxonomy_term']);
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldConfig);

    // Check that missing handler settings sets the field to not be mapping.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>This field is not mapped to a Schema.org property enumeration.</p><p><strong>Please update this taxonomy term's Schema.org type mapping.</strong></p>", $form['message']['#markup']);

    // Set the current route match to include the field config.
    $route_match = new RouteMatch(
      'test',
      new Route('test', ['field_config' => 'test']),
      ['field_config' => $this->fieldConfig]
    );
    $this->container->set('current_route_match', $route_match);
    $this->selectionHandler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($this->fieldConfig);

    // Check that handler settings are restored via the route match.
    // @see \Drupal\schemadotorg\Plugin\EntityReferenceSelection\SchemaDotOrgSelectionBase::buildConfigurationForm
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>Taxonomy terms (Gender Type) will be automatically available based this field's associated Schema.org property enumeration.</p>", $form['message']['#markup']);

    // Remove the user mapping to Schema.org gender property.
    $this->userMapping->set('properties', []);
    $this->userMapping->save();

    // Check displaying message when the enumeration is not found.
    $form = $this->selectionHandler->buildConfigurationForm($form, $form_state);
    $this->assertEquals("<p>This field is not mapped to a Schema.org property enumeration.</p><p><strong>Please update this taxonomy term's Schema.org type mapping.</strong></p>", $form['message']['#markup']);
  }

}
