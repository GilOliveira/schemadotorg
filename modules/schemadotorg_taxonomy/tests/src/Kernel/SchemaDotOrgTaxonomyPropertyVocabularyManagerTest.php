<?php

namespace Drupal\Tests\schemadotorg_taxonomy\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org taxonomy property vocabulary manager.
 *
 * @covers \Drupal\schemadotorg_taxonomy\SchemaDotOrgTaxonomyPropertyVocabularyManagerTest;
 * @group schemadotorg
 */
class SchemaDotOrgTaxonomyPropertyVocabularyManagerTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'taxonomy',
    'schemadotorg_taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installConfig(['schemadotorg_taxonomy']);
  }

  /**
   * Test Schema.org taxonomy property vocabulary manager.
   */
  public function testManager() {
    $this->createSchemaEntity('node', 'Recipe');

    /* ********************************************************************** */

    // Check that recipeCategory property defaults to
    // 'entity_reference:taxonomy_term' field type.
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'recipe', 'schema_recipe_cat');
    $this->assertEquals('default:taxonomy_term', $field_config->getSetting('handler'));
    $handler_settings = $field_config->getSetting('handler_settings');
    $this->assertEquals(['recipe_category' => 'recipe_category'], $handler_settings['target_bundles']);
    $this->assertTrue($handler_settings['auto_create']);

    // Check that recipe_category vocabulary is created.
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = Vocabulary::load('recipe_category');
    $this->assertEquals('recipe_category', $vocabulary->id());
    $this->assertEquals('Recipe category', $vocabulary->label());
    $this->assertEquals('The category of the recipe—for example, appetizer, entree, etc.', $vocabulary->getDescription());
  }

}
