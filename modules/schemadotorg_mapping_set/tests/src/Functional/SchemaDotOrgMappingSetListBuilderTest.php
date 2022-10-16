<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_subtype\Functional;

use Drupal\Core\Url;
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
    'paragraphs',
    'taxonomy',
    'block_content',
    'schemadotorg_ui',
    'schemadotorg_media',
    'schemadotorg_mapping_set',
  ];

  /**
   * Test Schema.org list builder enhancements.
   */
  public function testSchemaDotOrgListBuilder(): void {
    global $base_path;

    $assert_session = $this->assertSession();

    // Create image media entity to be mapping.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');

    $account = $this->drupalCreateUser([
      'administer schemadotorg',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($account);

    /* ********************************************************************** */

    // Check that no Schema.org mappings exists.
    $this->assertEmpty($mapping_storage->loadMultiple());

    // Check that the required and common mapping sets are displayed.
    $this->drupalGet('/admin/config/search/schemadotorg/sets');
    $assert_session->responseContains('Required');
    $assert_session->responseContains('<td>media:ImageObject, media:VideoObject, taxonomy_term:DefinedTerm, node:Person</td>');
    $assert_session->linkByHrefExists($base_path . 'admin/config/search/schemadotorg/sets/required/setup');
    $assert_session->responseContains('Common');
    $assert_session->linkByHrefExists($base_path . 'admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->responseContains('<td>media:AudioObject, media:DataDownload, media:ImageObject, media:VideoObject, taxonomy_term:DefinedTerm, node:Place, node:Organization, node:Person, node:Event, node:Article, node:WebPage</td>');

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
    $assert_session->responseContains('node:WebPage');

    // Update mapping set to just create a Person with a ContactPoint.
    $config = $this->config('schemadotorg_mapping_set.settings');
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

    // Check that the 'Add Schema.org content type' form for node:Place
    // displays a warning message.
    // @see schemadotorg_mapping_set_form_schemadotorg_mapping_add_form_alter()
    $setup_uri = Url::fromRoute(
      'schemadotorg_mapping_set.confirm_form',
      ['name' => 'common', 'operation' => 'setup'],
    )->toString();
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Place']]);
    $assert_session->linkByHrefExists($setup_uri);

    // Check that updated required and common mapping set types are displayed on the
    // confirm form.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/setup');
    $assert_session->responseContains('node:Place');
    $this->submitForm([], 'Confirm');

    // Check that the 'Add Schema.org content type' form for node:Place
    // DOES NOT display a warning message.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Place']]);
    $assert_session->linkByHrefNotExists($setup_uri);

    // Check that ContactPoint and Person Schema.org mappings exist.
    $this->assertEquals(['media.image', 'node.contact_point', 'node.place'], array_keys($mapping_storage->getQuery()->accessCheck(FALSE)->execute()));

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
    $this->assertEquals(10, count($node_storage->getQuery()->accessCheck(FALSE)->execute()));

    // Teardown the common mapping set.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/common/teardown');
    $this->submitForm([], 'Confirm');

    // Check node.place was removed.
    $this->assertEquals(['media.image', 'node.contact_point'], array_keys($mapping_storage->getQuery()->accessCheck(FALSE)->execute()));

    // Check that all generated nodes where deleted.
    $this->assertEquals(0, count($node_storage->getQuery()->accessCheck(FALSE)->execute()));

    // Teardown the required mapping set.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/required/teardown');
    $this->submitForm([], 'Confirm');

    // Check media.image and node.contact_point were removed.
    $this->assertEmpty($mapping_storage->getQuery()->accessCheck(FALSE)->execute());

    // Update mapping set to use invalid type.
    $config = $this->config('schemadotorg_mapping_set.settings');
    $config->set('sets', [
      'required' => [
        'label' => 'Required',
        'types' => ['not:Valid'],
      ],
    ])->save();

    // Check invalid type handling.
    $this->drupalGet('/admin/config/search/schemadotorg/sets');
    $assert_session->responseContains('Required');
    $assert_session->responseContains('<td><strong>not:Valid</strong></td>');
    $assert_session->responseContains('<em class="placeholder">not:Valid</em> in <em class="placeholder">Required</em> are not valid. <a href="' . $base_path . 'admin/config/search/schemadotorg/sets/settings">Please update this information.</a>');
    $assert_session->linkByHrefNotExists($base_path . 'admin/config/search/schemadotorg/sets/required/setup');
  }

}
