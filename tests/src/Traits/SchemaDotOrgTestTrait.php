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
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   * @param string $field_type
   *   (optional) The field type.  Defaults to 'string'.
   */
  protected function createSchemaDotOrgField(
    $entity_type_id,
    $schema_type,
    $schema_property = 'alternateName',
    $field_type = 'string'
  ) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names */
    $schema_names = $this->container->get('schemadotorg.names');

    $bundle = $schema_names->camelCaseToSnakeCase($schema_type);
    $field_name = $schema_names->getFieldPrefix() . $schema_names->toDrupalName('properties', $schema_property);
    $label = $schema_names->camelCaseToSentenceCase($schema_property);

    $field_storage_config = FieldStorageConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => $field_name,
      'type' => $field_type,
    ]);
    $field_storage_config->schemaDotOrgType = $schema_type;
    $field_storage_config->schemaDotOrgProperty = $schema_property;
    $field_storage_config->save();

    $field_config = FieldConfig::create([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => $field_name,
      'label' => $label,
    ]);
    $field_config->schemaDotOrgType = $schema_type;
    $field_config->schemaDotOrgProperty = $schema_property;
    $field_config->save();
  }

}
