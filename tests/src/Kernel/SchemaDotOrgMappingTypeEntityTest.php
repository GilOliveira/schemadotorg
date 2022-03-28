<?php

namespace Drupal\Tests\schemadotorg\Kernel;

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
  public static $modules = ['system', 'user', 'node', 'schemadotorg'];

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
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installConfig(['schemadotorg']);

    // Set Schema.org mapping type storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');
  }

  /**
   * Test Schema.org mapping entity.
   */
  public function testSchemaDotOrgMappingTypeEntity() {
    $user_mapping_type = $this->storage->load('user');
    $node_mapping_type = $this->storage->load('node');
    $paragaph_mapping_type = $this->storage->load('paragraph');

    // Check getting the mapping type label.
    $this->assertEquals('User', $user_mapping_type->label());
    $this->assertEquals('Content', $node_mapping_type->label());
    $this->assertEquals('paragraph', $paragaph_mapping_type->label());
  }

}
