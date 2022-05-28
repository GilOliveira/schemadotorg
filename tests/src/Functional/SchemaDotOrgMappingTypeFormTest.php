<?php

namespace Drupal\Tests\schemadotorg_ui\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org mapping type form.
 *
 * @covers \Drupal\schemadotorg\Form\SchemaDotOrgMappingTypeForm
 * @group schemadotorg
 */
class SchemaDotOrgMappingTypeFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['user', 'node'];

  /**
   * The Schema.org mapping type storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set Schema.org mapping type storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org mapping type form.
   */
  public function testSchemaDotOrgMappingTypeForm() {
    $assert_session = $this->assertSession();

    // Check that editing and re-saving the mapping type does not alter the
    // expected values.
    $mapping_type = $this->storage->load('node');
    $mapping_type_value = $mapping_type->toArray();
    $this->drupalGet('/admin/config/search/schemadotorg/types/node');
    $this->submitForm([], 'Save');
    $assert_session->responseContains('Updated <em class="placeholder">Content</em> mapping type.');
    $this->storage->resetCache();
    $mapping_type = $this->storage->load('node');
    $this->assertEquals($mapping_type_value, $mapping_type->toArray());
  }

}
