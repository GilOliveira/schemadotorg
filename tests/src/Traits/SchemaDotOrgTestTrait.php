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
  protected function createSchemaDotOrgField(
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
   * @param string $schema_type
   *   The Schema.org type.
   */
  protected function createSchemaDotOrgSubTypeField($entity_type_id, $bundle, $schema_type = '') {
    if ($schema_type) {
      /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManager $schema_type_manager */
      $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
      $allowed_values = $schema_type_manager->getAllTypeChildrenAsOptions($schema_type);
    }
    else {
      $allowed_values = [];
    }
    FieldStorageConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => 'schema_' . $bundle . '_subtype',
      'type' => 'list_string',
      'allowed_values' => $allowed_values,
    ])->save();
    FieldConfig::create([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => 'schema_' . $bundle . '_subtype',
    ])->save();
  }

}
