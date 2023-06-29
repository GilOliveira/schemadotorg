<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_type_tray\Kernel;

use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org type trayr.
 *
 * @group schemadotorg
 */
class SchemaDotOrgTypeTrayTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'type_tray',
    'schemadotorg_type_tray',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['type_tray', 'schemadotorg_type_tray']);

  }

  /**
   * Test Schema.org type tray.
   */
  public function testTypeTray(): void {
    global $base_path;

    // Check syncing grouped Schema.org types with type tray categories.
    // @covers _schemadotorg_type_tray_sync_schema_types_with_categories()
    $this->assertNull($this->config('type_tray.settings')->get('categories'));
    \Drupal::moduleHandler()->loadInclude('schemadotorg_type_tray', 'install');
    schemadotorg_type_tray_install(FALSE);
    $expected_categories = [
      'common' => 'Common',
      'web' => 'Web',
      'content' => 'Content',
      'organization' => 'Organization',
      'education' => 'Education',
      'food' => 'Food',
      'entertainment' => 'Entertainment',
      'medical_organization' => 'Medical organization',
      'medical_information' => 'Medical information',
    ];
    $this->assertEquals($expected_categories, $this->config('type_tray.settings')->get('categories'));

    // Check that type settings are added to the Schema.org Person node type.
    $mapping = $this->createSchemaEntity('node', 'Person');
    $expected_settings = [
      'type_category' => 'common',
      'type_icon' => $base_path . 'modules/sandbox/schemadotorg/modules/schemadotorg_type_tray/images/schemadotorg_type_tray/person.png',
      'existing_nodes_link_text' => 'View existing <em class="placeholder">Person</em> content',
      'type_weight' => 0,
    ];
    $node_type = $mapping->getTargetEntityBundleEntity();
    $this->assertEquals($expected_settings, $node_type->getThirdPartySettings('type_tray'));

  }

}
