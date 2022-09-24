<?php

namespace Drupal\Tests\schemadotorg\Kernel;

/**
 * Tests the Schema.org installer service.
 *
 * @coversDefaultClass \Drupal\schemadotorg\SchemaDotOrgInstaller
 * @group schemadotorg
 */
class SchemaDotOrgInstallerTest extends SchemaDotOrgKernelTestBase {

  /**
   * The Schema.org installer service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrginstallerInterface
   */
  protected $installer;

  /**
   * The Schema.org mapping type storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface
   */
  protected $mappingTypeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->mappingTypeStorage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');
  }

  /**
   * Tests SchemaDotOrgInstallerInterface::requirements().
   *
   * @covers ::requirements
   */
  public function testRequirements() {
    // Check installation requirements.
    $requirements = $this->installer->requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertEquals('Schema.org Blueprints: Recommended modules missing', $requirements['schemadotorg_modules']['title']);

    // Check creating mapping types for modules that provide a content entities.
    $this->assertNull($this->mappingTypeStorage->load('storage'));
    $this->installer->installModules(['storage']);
    $this->assertNotNull($this->mappingTypeStorage->load('storage'));
  }

}
