<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_metatag\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org metatag node edit form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgMetatagNodeEditFormTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'schemadotorg_metatag',
  ];
  // phpcs:enable

  /**
   * Test Schema.org metatag node edit form.
   */
  public function testNodeEditForm(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Create a WebPage (page) content type.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $mapping_manager */
    $mapping_manager = $this->container->get('schemadotorg.mapping_manager');
    $mapping_manager->createType('node', 'WebPage');

    // Check that metatag default groups set for the content type.
    $this->assertEquals(
      ['basic' => 'basic', 'advanced' => 'advanced'],
      $this->config('metatag.settings')->get('entity_type_groups.node.page')
    );

    // Check that meta tag description and noindex field exist.
    $this->drupalGet('/node/add/page');
    $assert_session->fieldExists('field_metatag[0][basic][description]');
    $assert_session->fieldExists('field_metatag[0][advanced][robots][noindex]');
    $assert_session->fieldNotExists('field_metatag[0][basic][abstact]');
    $assert_session->fieldNotExists('field_metatag[0][advanced][robots][nofollow]');

    $config = $this->config('schemadotorg_metatag.settings');
    $config->set('allowed_metatags', [
      'title',
      'description',
      'abstract',
      'keywords',
      'robots',
    ]);
    $config->set('allowed_robots', ['noindex', 'nofollow']);
    $config->save();

    // Check that meta tag abstract and nofollow field exist.
    $this->drupalGet('/node/add/page');
    $assert_session->fieldExists('field_metatag[0][basic][description]');
    $assert_session->fieldExists('field_metatag[0][advanced][robots][noindex]');
    $assert_session->fieldExists('field_metatag[0][basic][abstract]');
    $assert_session->fieldExists('field_metatag[0][advanced][robots][nofollow]');
  }

}
