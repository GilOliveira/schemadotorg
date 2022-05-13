<?php

namespace Drupal\Tests\schemadotorg_jsonapi\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;

/**
 * Tests the functionality of the Schema.org JSON:API manager.
 *
 * @covers \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManager;
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiManagerTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'jsonapi',
    'jsonapi_extras',
    'field',
    'node',
    'serialization',
    'system',
    'taxonomy',
    'text',
    'user',
    'file',
    'schemadotorg_jsonapi',
  ];

  /**
   * The JSON:API resource storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $resourceStorage;

  /**
   * The Schema.org mapping storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage
   */
  protected $mappingStorage;

  /**
   * Schema.org JSON:API manager.
   *
   * @var \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);
    $this->installConfig(['schemadotorg_jsonapi']);

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->installer->install();

    $this->mappingStorage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');
    $this->resourceStorage = $this->container->get('entity_type.manager')->getStorage('jsonapi_resource_config');
    $this->manager = $this->container->get('schemadotorg_jsonapi.manager');
  }

  /**
   * Test Schema.org JSON:API services.
   */
  public function testSchemaDotOrgJsonApi() {

    /* ********************************************************************** */
    // Test installing Schema.org mapping JSON:API resource config.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::install
    // @see schemadotorg_jsonapi_install()
    /* ********************************************************************** */

    // Trigger installation of the Schema.org JSON:API module.
    $this->manager->install();

    // Check JSON:API resources are imported and created on installation.
    // @see jsonapi_extras.jsonapi_resource_config.file--file.yml
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::installTaxonomyResource
    $resources = $this->resourceStorage->loadMultiple([
      'file--file',
      'taxonomy_term--schema_thing',
      'taxonomy_term--schema_enumeration',
    ]);
    $this->assertEquals(3, count($resources));

    // Get 'Thing' resource fields.
    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource */
    $resource = $resources['taxonomy_term--schema_thing'];
    $resource_fields = $resource->get('resourceFields');

    // Check enabling selected Schema.org taxonomy fields.
    $this->assertFalse($resource_fields['name']['disabled']);
    $this->assertFalse($resource_fields['status']['disabled']);
    $this->assertFalse($resource_fields['schema_type']['disabled']);

    // Check disabling internal taxonomy fields.
    $this->assertTrue($resource_fields['vid']['disabled']);
    $this->assertTrue($resource_fields['tid']['disabled']);
    $this->assertTrue($resource_fields['weight']['disabled']);

    // Check taxonomy field public names.
    $this->assertEquals('name', $resource_fields['name']['publicName']);
    $this->assertEquals('status', $resource_fields['status']['publicName']);
    $this->assertEquals('value', $resource_fields['schema_type']['publicName']);

    /* ********************************************************************** */
    // Insert Schema.org mapping JSON:API resource config.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::insertMappingResourceConfig
    /* ********************************************************************** */

    // Create Thing node with field.
    $node_type = NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ]);
    $node_type->save();
    $this->createSchemaDotOrgField('node', 'thing');
    $this->createSchemaDotOrgSubTypeField('node', 'thing');

    // Create Thing with mapping.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $thing_mapping */
    $thing_mapping = $this->mappingStorage->create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'type' => 'Thing',
      'subtype' => TRUE,
      'properties' => [
        'title' => 'name',
        'schema_alternate_name' => 'alternateName',
      ],
    ]);
    $thing_mapping->save();

    // Check that JSON:API resource was created for Thing.
    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource */
    $resource = $this->resourceStorage->load('node--thing');
    $resource_fields = $resource->get('resourceFields');

    // Check enabling selected Schema.org fields.
    $this->assertFalse($resource_fields['status']['disabled']);
    $this->assertFalse($resource_fields['langcode']['disabled']);
    $this->assertFalse($resource_fields['title']['disabled']);
    $this->assertFalse($resource_fields['schema_subtype']['disabled']);
    $this->assertFalse($resource_fields['schema_alternate_name']['disabled']);

    // Check disabling internal fields.
    $this->assertTrue($resource_fields['nid']['disabled']);
    $this->assertTrue($resource_fields['type']['disabled']);
    $this->assertTrue($resource_fields['changed']['disabled']);
    $this->assertTrue($resource_fields['created']['disabled']);

    // Check field public names.
    $this->assertEquals('name', $resource_fields['title']['publicName']);
    $this->assertEquals('subtype', $resource_fields['schema_subtype']['publicName']);
    $this->assertEquals('alternateName', $resource_fields['schema_alternate_name']['publicName']);

    /* ********************************************************************** */
    // Update Schema.org mapping JSON:API resource config.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::updateMappingResourceConfig
    /* ********************************************************************** */

    // Remove alterateName from mapping.
    $thing_mapping
      ->removeSchemaProperty('schema_alternate_name')
      ->save();

    // Check that existing resource field is unchanged.
    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertEquals('alternateName', $resource_fields['schema_alternate_name']['publicName']);

    /* ********************************************************************** */
    // Insert field into JSON:API resource config.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::insertFieldConfigResource
    /* ********************************************************************** */

    // Insert new field outside of the mapping.
    // Add some field.
    $this->createSchemaDotOrgField('node', 'thing', 'some_field', 'some_field');

    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertTrue($resource_fields['some_field']['disabled']);

    // Insert new Schema.org description field.
    // Note: Not using ::createSchemaDotOrgField because we need to set
    // 'schemaDotOrgAddFieldToEntity' to TRUE.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'schema_description',
      'type' => 'string',
    ])->save();
    $field_config = FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'thing',
      'field_name' => 'schema_description',
      'label' => 'schema_description',
    ]);
    // Set add field to entity flag, which ensure the JSON:API resource is
    // not updated until the mapping is saved.
    // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::addFieldToEntity
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::insertMappingFieldConfigResource
    $field_config->schemaDotOrgAddFieldToEntity = TRUE;
    $field_config->save();

    // Check not inserting field into JSON:API resource config if the Scheme.org
    // entity type builder is adding it via the 'schemaDotOrgAddFieldToEntity'
    // property.
    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertArrayNotHasKey('schema_description', $resource_fields);

    // Add description to the Thing mapping and save.
    $thing_mapping
      ->setSchemaPropertyMapping('schema_description', 'description')
      ->save();

    // Check that new Schema.org field is now added to the  JSON:API resource.
    $resource = $this->loadResource('node--thing');
    $resource_fields = $resource->get('resourceFields');
    $this->assertArrayHasKey('schema_description', $resource_fields);

    /* ********************************************************************** */
    // Handling conflicting JSON:API resource paths.
    // @see \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApi::getResourceConfigPath
    /* ********************************************************************** */

    // Create node:person.
    NodeType::create([
      'type' => 'person',
      'name' => 'Person',
    ])->save();
    $this->mappingStorage->create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'person',
      'type' => 'Person',
    ])->save();

    // Create user:person.
    $this->mappingStorage->create([
      'target_entity_type_id' => 'user',
      'target_bundle' => 'user',
      'type' => 'Person',
    ])->save();

    // Check that node:person use 'Person' as the path and name.
    $node_resource = $this->loadResource('node--person');
    $this->assertEquals('Person', $node_resource->get('path'));
    $this->assertEquals('Person', $node_resource->get('resourceType'));

    // Check that user:person use 'UserPerson' as the path and name.
    $user_resource = $this->loadResource('user--user');
    $this->assertEquals('UserPerson', $user_resource->get('path'));
    $this->assertEquals('UserPerson', $user_resource->get('resourceType'));
  }

  /**
   * Load a JSON:API resource.
   *
   * @param string $id
   *   Resource ID.
   *
   * @return \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig
   *   A JSON:API resource.
   */
  protected function loadResource($id) {
    $this->resourceStorage->resetCache([$id]);
    return $this->resourceStorage->load($id);
  }

}
