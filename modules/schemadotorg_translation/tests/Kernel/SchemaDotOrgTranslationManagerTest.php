<?php

namespace Drupal\Tests\schemadotorg_translation\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;
use Drupal\Tests\schemadotorg\Traits\SchemaDotOrgTestTrait;

/**
 * Tests the functionality of the Schema.org translation manager.
 *
 * @covers \Drupal\schemadotorg_translation\SchemaDotOrgTaxonomyPropertyVocabularyManagerTest;
 * @group schemadotorg
 */
class SchemaDotOrgTranslationManagerTest extends SchemaDotOrgKernelEntityTestBase {
  use SchemaDotOrgTestTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'language',
    'content_translation',
    'schemadotorg_translation',
  ];

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Schema.org translation manager.
   *
   * @var \Drupal\schemadotorg_translation\SchemaDotOrgTranslationManagerInterface
   */
  protected $schemaTranslationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('language_content_settings');
    $this->installConfig(['schemadotorg_translation']);

    ConfigurableLanguage::createFromLangcode('es')->save();

    $this->fieldManager = $this->container->get('entity_field.manager');
    $this->contentTranslationManager = $this->container->get('content_translation.manager');
    $this->schemaTranslationManager = $this->container->get('schemadotorg_translation.manager');
  }

  /**
   * Test Schema.org translation manager.
   */
  public function testManager() {
    /* ********************************************************************** */
    // Insert Schema.org mapping.
    // @see schemadotorg_translation_schemadotorg_mapping_insert()
    /* ********************************************************************** */

    // Create a Schema.org mapping.
    $this->createSchemaEntity('node', 'Place');

    // Check that node.place has translations enabled.
    // @see \Drupal\schemadotorg_translation\SchemaDotOrgTranslationManager::enableEntityType
    $this->assertNotNull(ContentLanguageSettings::load('node.place'));
    $this->contentTranslationManager->isEnabled('node', 'place');

    /* ********************************************************************** */
    // Insert field config.
    // @see schemadotorg_translation_field_config_insert()
    /* ********************************************************************** */

    // Check that node.place fields translations enabled.
    $field_definitions = $this->fieldManager->getFieldDefinitions('node', 'place');
    $this->assertTrue($field_definitions['title']->isTranslatable());
    $this->assertTrue($field_definitions['body']->isTranslatable());
    $this->assertTrue($field_definitions['schema_address']->isTranslatable());
    $this->assertFalse($field_definitions['schema_image']->isTranslatable());
    $this->assertFalse($field_definitions['schema_telephone']->isTranslatable());

    // Check property field added to a Schema.org has translation enabled.
    $this->createSchemaDotOrgField('node', 'place', 'schema_alternate_name');
    $field_definitions = $this->fieldManager->getFieldDefinitions('node', 'place');
    $this->assertTrue($field_definitions['schema_alternate_name']->isTranslatable());

    // Check any text field added to a Schema.org type has translation enabled.
    $this->createSchemaDotOrgField('node', 'place', 'field_text', 'Text');
    $field_definitions = $this->fieldManager->getFieldDefinitions('node', 'place');
    $this->assertTrue($field_definitions['field_text']->isTranslatable());

    // Check integer field added to a Schema.org type does not have
    // translation enabled.
    $this->createSchemaDotOrgField('node', 'place', 'field_integer', 'integer', 'integer');
    $field_definitions = $this->fieldManager->getFieldDefinitions('node', 'place');
    $this->assertFalse($field_definitions['field_integer']->isTranslatable());

    // Check excluded Schema.org properties.
    // Check excluded field names.
    // Check included field names.
    // Check included field types.
    // Check enable entity.
  }

}
