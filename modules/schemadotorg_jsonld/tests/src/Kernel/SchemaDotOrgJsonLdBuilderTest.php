<?php

namespace Drupal\Tests\schemadotorg_jsonld\Kernel;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD builder.
 *
 * @covers \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilder;
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdBuilderTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'options',
    'file',
    'serialization',
    'schemadotorg_jsonld',
  ];

  /**
   * The JSON-LD resource storage.
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
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);
    $this->installConfig(['schemadotorg_jsonld']);

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->installer->install();

    $this->mappingStorage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org JSON-LD builder.
   */
  public function testSchemaDotOrgJsonLd() {
    $this->assertTrue(TRUE);
  }

}
