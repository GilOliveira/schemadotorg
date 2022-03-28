<?php

namespace Drupal\Tests\schemadotorg_ui\Functional;

use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org UI route subscriber and more.
 *
 * @covers \Drupal\schemadotorg_ui\Routing\SchemaDotOrgRouteSubscriber
 * @covers \Drupal\schemadotorg_ui\Plugin\Derivative\SchemaDotOrgUiLocalAction
 * @covers \Drupal\schemadotorg_ui\Plugin\Derivative\SchemaDotOrgUiLocalTask
 * @covers \Drupal\schemadotorg_ui\Plugin\Derivative\SchemaDotOrgUiMenuLink
 *
 * @group schemadotorg
 */
class SchemaDotOrgUiRouteTest extends SchemaDotOrgBrowserTestBase {
  use MediaTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['block', 'user', 'node', 'media', 'field', 'field_ui', 'schemadotorg_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    $account = $this->drupalCreateUser([
      'administer user fields',
      'administer content types',
      'administer node fields',
      'administer media types',
      'administer media fields',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org UI routes, actions, tasks, and menu links.
   */
  public function testSchemaDotOrgUiRoutes() {
    global $base_path;

    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Routes.
    // @see \Drupal\schemadotorg_ui\Routing\SchemaDotOrgRouteSubscriber
    /* ********************************************************************** */

    // Check that node 'Add Schema.org type' route exists.
    $this->assertRouteExists('schemadotorg.node_type.type_add');
    $this->drupalGet('/admin/structure/types/schemadotorg');
    $assert_session->statusCodeEquals(200);

    // Check that media 'Add Schema.org type' route does exist.
    $this->assertRouteNotExists('schemadotorg.media_type.type_add');
    $this->drupalGet('/admin/structure/media/schemadotorg');
    $assert_session->statusCodeEquals(404);

    /* ********************************************************************** */
    // Local actions.
    // @see \Drupal\schemadotorg_ui\Plugin\Derivative\SchemaDotOrgUiLocalAction
    /* ********************************************************************** */

    // Check that node 'Add Schema.org type' action exists.
    $this->drupalGet('/admin/structure/types');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Add Schema.org type');
    $assert_session->linkByHrefExists($base_path . 'admin/structure/types/schemadotorg');

    // Check that media 'Add Schema.org type' action does not exist.
    $this->drupalGet('/admin/structure/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('Add Schema.org type');
    $assert_session->linkByHrefNotExists($base_path . 'admin/structure/media/schemadotorg');

    /* ********************************************************************** */
    // Local tasks.
    // @see \Drupal\schemadotorg_ui\Plugin\Derivative\SchemaDotOrgUiLocalTask
    /* ********************************************************************** */

    // Check that node 'Schema.org' task exists.
    $content_type = $this->drupalCreateContentType();
    $content_type_id = $content_type->id();
    $this->drupalGet("/admin/structure/types/manage/$content_type_id/fields");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Schema.org');
    $assert_session->linkByHrefExists($base_path . "admin/structure/types/manage/$content_type_id/schemedotorg");
    $this->drupalGet("/admin/structure/types/manage/$content_type_id/schemedotorg");
    $assert_session->statusCodeEquals(200);

    // Check that media 'Schema.org' task exists.
    $media_type = $this->createMediaType('image', ['id' => 'image']);
    $media_type_id = $media_type->id();
    $this->drupalGet("/admin/structure/media/manage/$media_type_id/fields");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Schema.org');
    $assert_session->linkByHrefExists($base_path . "admin/structure/media/manage/$media_type_id/schemedotorg");
    $this->drupalGet("/admin/structure/media/manage/$media_type_id/schemedotorg");
    $assert_session->statusCodeEquals(200);

    // Check that user 'Schema.org' task exists.
    $this->drupalGet("/admin/config/people/accounts/fields");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Schema.org');
    $assert_session->linkByHrefExists($base_path . "admin/config/people/accounts/schemedotorg");
    $this->drupalGet("/admin/config/people/accounts/schemedotorg");
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Menu links.
    // @see \Drupal\schemadotorg_ui\Plugin\Derivative\SchemaDotOrgUiMenuLink
    /* ********************************************************************** */

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    // Check that node 'Add Schema.org type' menu link exists.
    $menu_links = $menu_link_manager->loadLinksByRoute('schemadotorg.node_type.type_add');
    $this->assertCount(1, $menu_links);
    $menu_link = reset($menu_links);
    $this->assertEquals('entity.node_type.collection', $menu_link->getParent());

    // Check that media 'Add Schema.org type' menu link does not exist.
    $menu_links = $menu_link_manager->loadLinksByRoute('schemadotorg.media_type.type_add');
    $this->assertCount(0, $menu_links);
  }

  /**
   * Assert route exists.
   *
   * @param string $name
   *   A route name.
   */
  protected function assertRouteExists($name) {
    $router = $this->container->get('router');
    $this->assertTrue((boolean) $router->getRouteCollection()->get($name));
  }

  /**
   * Assert route not exists.
   *
   * @param string $name
   *   A route name.
   */
  protected function assertRouteNotExists($name) {
    $router = $this->container->get('router');
    $this->assertFalse((boolean) $router->getRouteCollection()->get($name));
  }

}
