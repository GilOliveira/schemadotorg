<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_custom_field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org Custom Field JSON-LD manager.
 */
class SchemaDotOrgCustomFieldJsonLdManager implements SchemaDotOrgCustomFieldJsonLdManagerInterface {

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * The Schema.org Custom Field manager.
   *
   * @var \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldManagerInterface
   */
  protected $schemaCustomFieldManager;

  /**
   * Constructs a SchemaDotOrgCustomFieldJsonLdManager object.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldManagerInterface $schema_custom_field_manager
   *   The Schema.org Custom Field manager.
   */
  public function __construct(
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgCustomFieldManagerInterface $schema_custom_field_manager,
  ) {
    $this->schemaTypeManager = $schema_type_manager;
    $this->schemaNames = $schema_names;
    $this->schemaCustomFieldManager = $schema_custom_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonLdSchemaPropertyAlter(mixed &$value, FieldItemInterface $item): void {
    $mapping = $this->schemaCustomFieldManager->getFieldItemSchemaMapping($item);
    if (!$mapping) {
      return;
    }

    $field_name = $item->getFieldDefinition()->getName();
    $mapping_schema_type = $mapping->getSchemaType();
    $schema_property = $mapping->getSchemaPropertyMapping($field_name);

    // Check to see if the property has custom field settings.
    $default_properties = $this->schemaCustomFieldManager->getDefaultProperties($mapping_schema_type, $schema_property);
    if (!$default_properties) {
      return;
    }

    $data = [
      '@type' => $default_properties['type'],
    ];
    $values = $item->getValue();
    foreach ($values as $item_key => $item_value) {
      $item_property = $this->schemaNames->snakeCaseToCamelCase($item_key);
      $has_value = ($item_value !== '' && $item_value !== NULL);
      $is_property = $this->schemaTypeManager->isProperty($item_property);
      if (!$has_value || !$is_property) {
        continue;
      }

      $unit = $this->schemaTypeManager->getPropertyUnit($item_property, $item_value);
      if ($unit) {
        $item_value .= ' ' . $unit;
      }

      $data[$item_property] = $item_value;
    }
    $value = $data;
  }

}
