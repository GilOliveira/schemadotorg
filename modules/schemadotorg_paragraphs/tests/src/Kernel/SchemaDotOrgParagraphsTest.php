<?php

namespace Drupal\Tests\schemadotorg_paragraphs\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org paragraphs.
 *
 * @covers schemadotorg_paragraphs_schemadotorg_property_field_alter()
 * @covers schemadotorg_paragraphs_schemadotorg_mapping_presave()
 * @group schemadotorg
 */
class SchemaDotOrgParagraphsTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'paragraphs_library',
    'schemadotorg_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['schemadotorg_paragraphs']);
  }

  /**
   * Test Schema.org paragraphs.
   */
  public function testParagraphs() {
    $this->createSchemaEntity('paragraph', 'ContactPoint');
    $this->createSchemaEntity('node', 'Person');

    /* ********************************************************************** */

    // Check that ContactPoint field target bundles includes the
    // 'from_library' paragraph type.
    // @see schemadotorg_paragraphs_schemadotorg_property_field_alter()
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::loadByName('node', 'person', 'schema_contact_point');
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertEquals(['contact_point', 'from_library'], array_values($handler_settings['target_bundles']));

    // Check that ContactPoint paragraph type support library conversion.
    // @see schemadotorg_paragraphs_schemadotorg_mapping_presave()
    $paragraph_type = ParagraphsType::load('contact_point');
    $this->assertTrue($paragraph_type->getThirdPartySetting('paragraphs_library', 'allow_library_conversion'));
  }

}