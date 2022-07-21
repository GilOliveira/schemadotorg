<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Tests the Schema.org mapping manager service.
 *
 * @coversDefaultClass \Drupal\schemadotorg\SchemaDotOrgApi
 * @group schemadotorg
 */
class SchemaDotOrgMappingManagerTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'field',
    'field_ui',
    'file',
    'datetime',
    'image',
    'system',
    'telephone',
    'text',
    'link',
    'options',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org mapping manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $mappingManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installEntitySchema('user');

    $this->installConfig(['schemadotorg']);
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Get the Schema.org mapping manager.
    $this->mappingManager = $this->container->get('schemadotorg.mapping_manager');
  }

  /**
   * Tests SchemaDotOrgMappingManager.
   */
  public function testMappingManager() {
    // Checking getting ignored Schema.org properties.
    $this->assertArrayHasKey('accessMode', $this->mappingManager->getIgnoredProperties());

    // Check getting Schema.org mapping default values.
    $defaults = $this->mappingManager->getMappingDefaults('node', NULL, 'Event');
    $this->assertEquals('Event', $defaults['entity']['label']);
    $this->assertEquals('event', $defaults['entity']['id']);
    $this->assertStringStartsWith('An event', $defaults['entity']['description']);
    $expected = [
      'name' => 'title',
      'type' => 'string',
      'label' => 'Name',
      'machine_name' => 'name',
      'unlimited' => FALSE,
      'description' => 'The name of the item.',
    ];
    $this->assertEquals($expected, $defaults['properties']['name']);

    // Check getting Schema.org mapping default values with custom bundle.
    $defaults = $this->mappingManager->getMappingDefaults('node', 'custom', 'Event');
    $this->assertEquals('custom', $defaults['entity']['id']);

    // Check saving a Schema.org mapping.
    $defaults = $this->mappingManager->getMappingDefaults('node', NULL, 'Event');
    $mapping = $this->mappingManager->saveMapping('node', 'Event', $defaults);
    $this->assertEquals('node', $mapping->getTargetEntityTypeId());
    $this->assertEquals('event', $mapping->getTargetBundle());
    $this->assertEquals('Event', $mapping->getSchemaType());

    // Check create entity type validation.
    try {
      $this->mappingManager->createTypeValidate('not_entity', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "The entity type 'not_entity' is not valid. Please select a entity type (node, user).");
    }

    // Check create schema type validation.
    try {
      $this->mappingManager->createTypeValidate('node', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "The Schema.org type 'not_schema' is not valid.");
    }

    // Check creating user:Person type.
    $this->mappingManager->createType('user', 'Person');
    $mapping = SchemaDotOrgMapping::load('user.user');
    $this->assertEquals('user', $mapping->getTargetEntityTypeId());
    $this->assertEquals('user', $mapping->getTargetBundle());

    /* ********************************************************************** */
    // Delete.
    /* ********************************************************************** */

    // Check delete schema mapping validation.
    try {
      $this->mappingManager->deleteTypeValidate('node', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "No Schema.org mapping exists for not_schema (node).");
    }

    // Check deleting user:Person type.
    $this->mappingManager->deleteType('user', 'Person');
    \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping')->resetCache();
    $this->assertNull(SchemaDotOrgMapping::load('user.user'));
  }

}
