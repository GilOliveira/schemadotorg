<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_demo_umami_content;

use Drupal\demo_umami_content\InstallHelper;
use Drupal\Component\Utility\Html;

include \Drupal::root() . '/core/profiles/demo_umami/modules/demo_umami_content/src/InstallHelper.php';

/**
 * Defines a helper class for importing default content.
 *
 * @internal
 *   This code is only for use by the Umami demo: Content module.
 *
 * @see \Drupal\demo_umami_content\InstallHelper
 */
class SchemaDotOrgDemoUmamiContentInstallHelper extends InstallHelper {

  /**
   * {@inheritdoc}
   */
  public function importContent() {
    $this->getModulePath()
      ->importEditors()
      ->importContentFromFile('taxonomy_term', 'tags')
      ->importContentFromFile('taxonomy_term', 'recipe_category')
      ->importContentFromFile('media', 'image')
      ->importContentFromFile('node', 'recipe')
      ->importContentFromFile('node', 'article')
      ->importContentFromFile('node', 'page');
  }

  /**
   * {@inheritdoc}
   */
  protected function getModulePath() {
    $this->module_path = \Drupal::root() . '/core/profiles/demo_umami/modules/demo_umami_content';
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function processRecipe(array $data, $langcode) {
    $mapping = [
      'field_media_image' => 'schema_image',
      'field_summary' => 'body',
      'field_recipe_category' => 'schema_recipe_category',
      'field_preparation_time' => 'schema_prep_time',
      'field_cooking_time' => 'schema_cook_time',
      'field_difficulty' => 'schema_edu_level',
      'field_number_of_servings' => 'schema_recipe_yield',
      'field_ingredients' => 'schema_recipe_ingredient',
      'field_recipe_instruction' => 'schema_recipe_instructions',
    ];

    $values = parent::processRecipe($data, $langcode);

    // Trim ingredients.
    foreach ($values['field_ingredients'] as &$item) {
      $item['value'] = trim($item['value']);
    }

    // Parse instructions.
    $dom = Html::load($values['field_recipe_instruction'][0]['value']);
    $items = [];
    foreach ($dom->getElementsByTagName('li') as $dom_node) {
      $items[] = ['value' => $dom_node->textContent];
    }
    $values['field_recipe_instruction'] = $items;

    return $this->applyMapping($values, $mapping);
  }

  /**
   * Apply mapping from Umami fields to Schema.org fields.
   *
   * @param array $values
   *   An associative array of values.
   * @param array $mapping
   *   An associative array containing source to destination mappings.
   *
   * @return array
   *   Mapped values.
   */
  protected function applyMapping(array $values, array $mapping): array {
    foreach ($mapping as $source => $destination) {
      if (isset($values[$source])) {
        $value = $values[$source];
        unset($values[$source]);
        $values[$destination] = $value;
      }
    }
    return $values;
  }

}
