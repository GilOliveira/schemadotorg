<?php

namespace Drupal\Tests\schemadotorg\Kernel;

/**
 * Tests the Schema.org install modules hook.
 *
 * @covers schemadotorg_modules_installed()
 * @group schemadotorg
 */
class SchemaDotOrgInstallModuleTest extends SchemaDotOrgKernelTestBase {

  /**
   * Tests Schema.org install modules hook.
   */
  public function testModulesInstall() {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping_type');

    // Check that storage mapping type does not exist.
    $this->assertNull($mapping_type_storage->load('storage'));

    // Simulate installing the Storage module.
    schemadotorg_modules_installed(['storage']);

    // Check that storage mapping type does exist.
    $this->assertNotNull($mapping_type_storage->load('storage'));
  }

}
