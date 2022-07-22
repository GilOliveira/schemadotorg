<?php

namespace Drupal\Tests\schemadotorg_subtype\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org subtype module.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSubtypeTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'schemadotorg_ui',
    'schemadotorg_subtype',
  ];

  /**
   * Test Schema.org subtype UI.
   */
  public function testSubtype() {
    $assert_session = $this->assertSession();

    $config = \Drupal::config('schemadotorg_subtype.settings');

    /* ********************************************************************** */
    // Mapping defaults.
    // @see schemadotorg_subtype_schemadotorg_mapping_defaults_alter()
    /* ********************************************************************** */

    // Check mapping default for Schema.type that supports subtyping.
    $defaults = $this->getMappingDefaults('node', NULL, 'Person');
    $this->assertArrayHasKey('subtype', $defaults['properties']);
    $this->assertEquals('', $defaults['properties']['subtype']['name']);
    $this->assertEquals('list_string', $defaults['properties']['subtype']['type']);
    $this->assertEquals('Subtype', $defaults['properties']['subtype']['label']);
    $this->assertEquals('person_subtype', $defaults['properties']['subtype']['machine_name']);
    $this->assertEquals($config->get('default_field_description'), $defaults['properties']['subtype']['description']);
    $this->assertEquals(['Patient' => 'Patient'], $defaults['properties']['subtype']['allowed_values']);

    // Check mapping default for Schema.type that does not support subtyping.
    $defaults = $this->getMappingDefaults('node', NULL, 'Patient');
    $this->assertArrayNotHasKey('properties', $defaults);

    // Check mapping default for Schema.type that has subtype enabled.
    $defaults = $this->getMappingDefaults('node', NULL, 'Event');
    $this->assertEquals('_add_', $defaults['properties']['subtype']['name']);

    /* ********************************************************************** */
    // Schema.org mapping UI form alter.
    // @see schemadotorg_subtype_form_schemadotorg_mapping_form_alter()
    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    // Check no subtype field on Schema.org type select form.
    $this->drupalGet('/admin/structure/types/schemadotorg');
    $assert_session->responseNotContains('Enable Schema.org subtyping');

    // Check that subtype field appears but is not checked by default.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Person']]);
    $assert_session->responseContains('Enable Schema.org subtyping');
    $assert_session->checkboxNotChecked('properties[subtype][field][name]');

    // Check that subtype field does appear when not supported.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Patient']]);
    $assert_session->responseNotContains('Enable Schema.org subtyping');

    // Check that subtype field is checked by default.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Event']]);
    $assert_session->responseContains('Enable Schema.org subtyping');
    $assert_session->checkboxChecked('properties[subtype][field][name]');

    // Create the Event Schema.org type mapping.
    $this->submitForm([], 'Save');
    $assert_session->responseContains('The content type <em class="placeholder">Event</em> has been added.');

    // Check mapping defaults for existing Schema.type just return the field name..
    $defaults = $this->getMappingDefaults('node', 'event', 'Event');
    $this->assertEquals(['name' => 'schema_event_subtype'], $defaults['properties']['subtype']);

    /* ********************************************************************** */
    // Mapping defaults configuration.
    // @see schemadotorg_subtype_form_schemadotorg_types_settings_form_alter()
    /* ********************************************************************** */

    // Update subtype configuration settings.
    $this->drupalGet('/admin/config/search/schemadotorg/settings');
    $edit = [
      'subtype_default_field_label' => 'Type',
      'subtype_default_field_suffix' => '_type',
      'subtype_default_field_description' => 'Custom subtype description',
      'subtype_default_subtypes' => 'Person',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check mapping defaults with new subtype configuration settings.
    $defaults = $this->getMappingDefaults('node', NULL, 'Person');
    $this->assertEquals('_add_', $defaults['properties']['subtype']['name']);
    $this->assertEquals('Type', $defaults['properties']['subtype']['label']);
    $this->assertEquals('person_type', $defaults['properties']['subtype']['machine_name']);
    $this->assertEquals('Custom subtype description', $defaults['properties']['subtype']['description']);
  }

  /**
   * Get the mapping defaults for a Schema.org mapping.
   *
   * @param string $entity_type_id
   *   THe entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return array
   *   The mapping defaults.
   */
  protected function getMappingDefaults($entity_type_id, $bundle, $schema_type) {
    $defaults = [];
    schemadotorg_subtype_schemadotorg_mapping_defaults_alter($entity_type_id, $bundle, $schema_type, $defaults);
    return $defaults;
  }

}
