<?php

namespace Drupal\Tests\schemadotorg_descriptions\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests for Schema.org descriptions.
 *
 * @group schemadotorg
 */
class SchemaDotOrgDescriptionsTest extends SchemaDotOrgBrowserTestBase {

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
    'schemadotorg_ui',
  ];

  /**
   * Test Schema.org descriptions.
   */
  public function testDescriptions() {
    $assert_session = $this->assertSession();

    // Login as node type administrator.
    $account = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($account);

    // Check add content type, subtype, and field descriptions.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Thing']]);
    $assert_session->fieldValueEquals('entity[description]', 'The most generic type of item.');
    $assert_session->fieldValueEquals('subtyping[_add_][description]', 'A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.');
    $assert_session->fieldValueEquals('properties[description][field][_add_][description]', 'A description of the item.');

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->install(['schemadotorg_descriptions']);

    // Check add content type, subtype, and field descriptions are empty and
    // the element's #description is updated.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Thing']]);
    $assert_session->fieldValueEquals('entity[description]', '');
    $assert_session->fieldValueEquals('subtyping[_add_][description]', '');
    $assert_session->fieldValueEquals('properties[description][field][_add_][description]', '');
    $assert_session->responseContains("<strong>If left blank, the description will be automatically set to the corresponding Schema.org type's comment.</strong>");
    $assert_session->responseContains("<strong>If left blank, the description will be automatically set.</strong>");

    // Create the 'Thing' content type with type and alternateName fields.
    $edit = [
      'subtyping[enable]' => TRUE,
      'properties[alternateName][field][name]' => '_add_',
    ];
    $this->submitForm($edit, 'Save');

    // Create another random content type to enable the node add page.
    $this->drupalCreateContentType();

    // Login as root user since we are not testing node access.
    $this->drupalLogin($this->rootUser);

    // Check that the description is automatically added to the node types page.
    $this->drupalGet('/admin/structure/types');
    $assert_session->responseContains('The most generic type of item.');

    // Check that the descriptions is automatically added to the node add page.
    $this->drupalGet('/node/add');
    $assert_session->responseContains('The most generic type of item.');

    // Check that descriptions are automatically added to the node edit form.
    $this->drupalGet('/node/add/thing');
    $assert_session->responseContains('A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.');
    $assert_session->responseContains('An alias for the item.');

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->uninstall(['schemadotorg_descriptions']);

    // Check that the descriptions are not added to the node add page.
    $this->drupalGet('/node/add');
    $assert_session->responseNotContains('The most generic type of item.');

    // Check that descriptions are not added to the node edit form.
    $this->drupalGet('/node/add/thing');
    $assert_session->responseNotContains('A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.');
    $assert_session->responseNotContains('An alias for the item.');
  }

}
