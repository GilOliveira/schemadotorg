<?php

namespace Drupal\Tests\schemadotorg\Traits;

use Drupal\Component\Render\MarkupInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides convenience methods for Schema.org assertions.
 */
trait SchemaDotOrgTestTrait {

  /**
   * Convert all render(able) markup into strings.
   *
   * @param array $elements
   *   An associative array of elements.
   */
  protected function convertMarkupToStrings(array &$elements) {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        $this->convertMarkupToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
    }
  }

  /**
   * Create Schema.org field.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $field_name
   *   (optional) The field name. Defaults to 'schema_alternate_name'.
   * @param string $label
   *   (optional) The field label. Defaults to 'Alternate name'.
   * @param string $type
   *   (optional) The field type.  Defaults to 'string'.
   */
  protected function createField(
    $entity_type_id,
    $bundle,
    $field_name = 'schema_alternate_name',
    $label = 'Alternate name',
    $type = 'string'
  ) {
    FieldStorageConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => $field_name,
      'type' => $type,
    ])->save();
    FieldConfig::create([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => $field_name,
      'label' => $label,
    ])->save();
  }

  /**
   * Create Schema.org subtype field.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   */
  protected function createSubTypeField($entity_type_id, $bundle) {
    FieldStorageConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => 'schema_type',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
    ])->save();
    FieldConfig::create([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => 'schema_type',
      'settings' => [
        'handler' => 'schemadotorg_type',
        'handler_settings' => [
          'target_type' => 'taxonomy_term',
          'depth' => 1,
          'schemadotorg_mapping' => [
            'entity_type' => $entity_type_id,
            'bundle' => $bundle,
            'field_name' => 'schema_type',
          ],
        ],
      ],
    ])->save();
  }

}
