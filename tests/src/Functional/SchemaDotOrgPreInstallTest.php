<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_ui\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org preinstall hook.
 *
 * @covers schemadotorg_module_preinstall()
 * @group schemadotorg
 */
class SchemaDotOrgPreInstallTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_preinstall_test'];

  /**
   * Test Schema.org actions before a module is installed.
   */
  public function testPreInstall(): void {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $node_type_storage */
    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');
    $this->assertNotNull($node_type_storage->load('person'));
    $this->assertNotNull($node_type_storage->load('event'));

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');
    $this->assertNotNull($mapping_storage->load('node.person'));
    $this->assertNotNull($mapping_storage->load('node.event'));

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    $this->assertNotNull($view_storage->load('events'));
  }

}
