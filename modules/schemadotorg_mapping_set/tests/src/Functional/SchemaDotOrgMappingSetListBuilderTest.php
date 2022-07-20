<?php

namespace Drupal\Tests\schemadotorg_subtype\Functional;

use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org mapping set list builder.
 *
 * @group schemadotorg
 */
class SchemaDotOrgMappingSetListBuilderTest extends SchemaDotOrgBrowserTestBase {
  use MediaTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'media',
    'schemadotorg_mapping_set',
  ];

  /**
   * Test Schema.org list builder enhancements.
   */
  public function testSchemaDotOrgListBuilder() {
    global $base_path;

    $assert_session = $this->assertSession();

    // Create image media entity to be mapping.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);

    /* ********************************************************************** */

    // Check that no Schema.org mappings exists.
    $this->assertEmpty($mapping_storage->loadMultiple());

    // Check that the required and common mapping sets are displayed.
    $this->drupalGet('/admin/config/search/schemadotorg/sets');
    $assert_session->responseContains('Required');
    $assert_session->responseContains('<td>media:ImageObject, media:VideoObject, taxonomy_term:DefinedTerm, paragraph:ContactPoint, node:Person</td>');
    $assert_session->linkByHrefExists($base_path . 'admin/config/search/schemadotorg/sets/required/setup');
    $assert_session->responseContains('Common');
    $assert_session->linkByHrefExists($base_path . 'admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->responseContains('<td>media:AudioObject, media:DataDownload, media:ImageObject, media:VideoObject, taxonomy_term:DefinedTerm, paragraph:ContactPoint, node:Place, node:Organization, node:Person, node:Event, node:Article, node:WebPage</td>');

    // Check access allowed to common setup confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->statusCodeEquals(200);
    // Check access denied to common teardown, generate, and kill confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/teardown');
    $assert_session->statusCodeEquals(404);
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/generate');
    $assert_session->statusCodeEquals(404);
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/kill');
    $assert_session->statusCodeEquals(404);

    // Check that required and common mapping set types are displayed on the
    // confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->responseContains('media:AudioObject');
    $assert_session->responseContains('paragraph:ContactPoint');
    $assert_session->responseContains('node:WebPage');

    // Update mapping set to just create a Person with a ContactPoint.
    $config = \Drupal::configFactory()->getEditable('schemadotorg_mapping_set.settings');
    $config->set('sets', [
      'required' => [
        'label' => 'Required',
        'types' => ['node:ContactPoint', 'media:ImageObject'],
      ],
      'common' => [
        'label' => 'Common',
        'types' => ['node:Place'],
      ],
    ])->save();

    // Check that the required and common mapping sets are updated.
    $this->drupalGet('/admin/config/search/schemadotorg/sets');
    $assert_session->responseContains('Required');
    $assert_session->responseContains('<td>node:ContactPoint, media:ImageObject</td>');
    $assert_session->responseContains('Common');
    $assert_session->responseContains('<td>node:Place</td>');

    // Check that updated required and common mapping set types are displayed on the
    // confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->responseContains('node:Place');
    $this->submitForm([], 'Confirm');

    // Check that ContactPoint and Person Schema.org mappings exist.
    $this->assertEquals(['media.image', 'node.contact_point', 'node.place'], array_keys($mapping_storage->getQuery()->execute()));

    // Check the common mapping set operations have changed but
    // generate and kill operations are missing.
    $this->drupalGet('/admin/config/search/schemadotorg/sets');
    $assert_session->linkByHrefNotExists($base_path . 'admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->linkByHrefNotExists($base_path . 'admin/config/search/schemadotorg/sets/common/generate');
    $assert_session->linkByHrefNotExists($base_path . 'admin/config/search/schemadotorg/sets/common/kill');
    $assert_session->linkByHrefExists($base_path . 'admin/config/search/schemadotorg/sets/common/teardown');

    // Check access denied to common setup confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->statusCodeEquals(404);
    // Check access allowed to common teardown, generate, and kill confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/teardown');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/generate');
    $assert_session->statusCodeEquals(404);
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/kill');
    $assert_session->statusCodeEquals(404);

    // Install the devel_generate.module.
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['devel_generate']);

    // Check the common mapping now has generate and kill operations.
    $this->drupalGet('/admin/config/search/schemadotorg/sets');
    $assert_session->linkByHrefExists($base_path . 'admin/config/search/schemadotorg/sets/common/generate');
    $assert_session->linkByHrefExists($base_path . 'admin/config/search/schemadotorg/sets/common/kill');

    // Check access denied to common setup confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->statusCodeEquals(404);
    // Check access allowed to common teardown, generate, and kill confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/teardown');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/generate');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/kill');
    $assert_session->statusCodeEquals(200);

    // Generate common mapping set nodes.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/generate');
    $this->submitForm([], 'Confirm');

    // Check that 10 nodes where created.
    $this->assertEquals(10, count($node_storage->getQuery()->execute()));

    // Teardown the common mapping set.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/teardown');
    $this->submitForm([], 'Confirm');

    // Check node.place was removed.
    $this->assertEquals(['media.image', 'node.contact_point'], array_keys($mapping_storage->getQuery()->execute()));

    // Check that all generated nodes where deleted.
    $this->assertEquals(0, count($node_storage->getQuery()->execute()));

    // Teardown the required mapping set.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/required/teardown');
    $this->submitForm([], 'Confirm');

    // Check media.image and node.contact_point were removed.
    $this->assertEmpty($mapping_storage->getQuery()->execute());
  }

}
