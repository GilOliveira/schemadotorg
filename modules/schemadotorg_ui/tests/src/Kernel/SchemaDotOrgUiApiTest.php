<?php

namespace Drupal\Tests\schemadotorg_ui\Kernel;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;

/**
 * Tests the Schema.org UI API service.
 *
 * @coversDefaultClass \Drupal\schemadotorg_ui\SchemaDotOrgUiApi
 * @group schemadotorg
 */
class SchemaDotOrgUiApiTest extends SchemaDotOrgKernelTestBase {

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
    'telephone',
    'text',
    'link',
    'options',
    'schemadotorg_ui',
  ];

  /**
   * The Schema.org UI API service.
   *
   * @var \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface
   */
  protected $api;

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

    $this->api = $this->container->get('schemadotorg_ui.api');
  }

  /**
   * Tests SchemaDotOrgUi::createTypeValidate().
   *
   * @covers ::createTypeValidate
   */
  public function testValidate() {
    // Check create entity type validation.
    try {
      $this->api->createTypeValidate('not_entity', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "The entity type 'not_entity' is not valid. Please select a entity type (node, user).");
    }

    // Check create schema type validation.
    try {
      $this->api->createTypeValidate('node', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "The Schema.org type 'not_schema' is not valid.");
    }

    // Check creating user:Person type.
    $this->api->createType('user', 'Person');
    $mapping = SchemaDotOrgMapping::load('user.user');
    $this->assertEquals('user', $mapping->getTargetEntityTypeId());
    $this->assertEquals('user', $mapping->getTargetBundle());

    /* ********************************************************************** */
    // Delete.
    /* ********************************************************************** */

    // Check delete schema mapping validation.
    try {
      $this->api->deleteTypeValidate('node', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "No Schema.org mapping exists for not_schema (node).");
    }

    // Check deleting user:Person type.
    $this->api->deleteType('user', 'Person');
    \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping')->resetCache();
    $this->assertNull(SchemaDotOrgMapping::load('user.user'));
  }

}
