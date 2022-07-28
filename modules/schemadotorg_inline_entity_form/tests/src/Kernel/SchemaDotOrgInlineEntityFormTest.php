<?php

namespace Drupal\Tests\schemadotorg_inline_entity_form\Kernel;

use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org inline entity form.
 *
 * @covers _schemadotorg_inline_entity_form_enabled()
 * @covers schemadotorg_inline_entity_form_schemadotorg_property_field_alter()
 * @group schemadotorg
 */
class SchemaDotOrgInlineEntityFormTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'inline_entity_form',
    'schemadotorg_inline_entity_form',
  ];

  /**
   * Test Schema.org inline entity form.
   */
  public function testInlineEntityForm() {
    // Use an inline entity form for Person:alumniOf.
    \Drupal::configFactory()->getEditable('schemadotorg_inline_entity_form.settings')
      ->set('default_properties', ['Person--alumniOf'])
      ->save();

    // Create organization to be used as the entity reference target for
    // Patient:alumniOf.
    $this->createSchemaEntity('node', 'Organization');

    // Create a patient instead of a person to test inheritance.
    // @see _schemadotorg_inline_entity_form_enabled()
    $this->createSchemaEntity('node', 'Patient');

    /* ********************************************************************** */

    // Check that the alumniOf property/field use an inline entity form.
    // @see schemadotorg_inline_entity_form_schemadotorg_property_field_alter()
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $form_display = $entity_display_repository->getFormDisplay('node', 'patient', 'default');
    $component = $form_display->getComponent('schema_alumni_of');
    $this->assertEquals('inline_entity_form_complex', $component['type']);
    $this->assertTrue($component['settings']['allow_existing']);
    $this->assertTrue($component['settings']['allow_duplicate']);
    $this->assertTrue($component['settings']['collapsible']);
    $this->assertTrue($component['settings']['revision']);
  }

}
