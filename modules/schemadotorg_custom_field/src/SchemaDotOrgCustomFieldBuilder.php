<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_custom_field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org Custom Field builder.
 */
class SchemaDotOrgCustomFieldBuilder implements SchemaDotOrgCustomFieldBuilderInterface {

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
   * Constructs a SchemaDotOrgCustomFieldBuilder object.
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
  public function fieldWidgetFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $context['items'];

    $mapping = $this->schemaCustomFieldManager->getFieldItemSchemaMapping($items);
    if (!$mapping) {
      return;
    }

    $field_name = $items->getFieldDefinition()->getName();
    $schema_type = $mapping->getSchemaType();
    $schema_property = $mapping->getSchemaPropertyMapping($field_name);

    // Check to see if the property has custom field settings.
    if (!$this->schemaCustomFieldManager->getDefaultProperties($schema_type, $schema_property)) {
      return;
    }

    $children = Element::children($element);
    foreach ($children as $child_key) {
      $property = $this->schemaNames->snakeCaseToCamelCase($child_key);
      $unit = $this->schemaTypeManager->getPropertyUnit($property);
      if ($unit) {
        $element[$child_key]['#field_suffix'] = $unit;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessCustomField(array &$variables): void {
    foreach ($variables['items'] as $index => &$item) {
      if ($item['value'] === '') {
        unset($variables['items'][$index]);
        continue;
      }

      // Append property unit to numeric value.
      $property = $this->schemaNames->snakeCaseToCamelCase($item['name']);
      $unit = $this->schemaTypeManager->getPropertyUnit($property, $item['value']);
      if ($unit) {
        $item['value'] .= ' ' . $unit;
      }
    }
  }

}
