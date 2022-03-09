<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Schema.org entity type manager.
 */
class SchemaDotOrgEntityTypeManager implements SchemaDotOrgEntityTypeManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgEntityTypeManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    return [
      'block_content',
      'node',
      'media',
      'paragraph',
      'user',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypesAsOptions() {
    // Get field types as options.
    $options = [];
    $field_types = $this->fieldTypeManager->getUiDefinitions();
    foreach ($field_types as $name => $field_type) {
      if (empty($field_type['no_ui'])) {
        $options[$name] = $field_type['label'];
      }
    }
    asort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyFieldTypesAsOptions($property) {
    $property_mappings = [
      'description' => ['text_long', 'text', 'text_with_summary'],
      'disambiguatingDescription' => ['text_long', 'text', 'text_with_summary'],
      'identifier' => ['key_value', 'key_value_long'],
      'image' => ['field_ui:entity_reference:media', 'image'],
      'telephone' => ['telephone'],
    ];

    $data_type_mappings = [
      // Data types.
      'Text' => ['string', 'string_long', 'list_string', 'text', 'text_long', 'text_with_summary'],
      'Number' => ['integer', 'float', 'decimal', 'list_integer', 'list_float'],
      'DateTime' => ['datetime'],
      'Date' => ['datetime'],
      'Integer' => ['integer', 'list_integer'],
      'Time' => ['datetime'],
      'Boolean' => ['boolean'],
      'URL' => ['link'],
      // @todo Things.
      // @todo Enumerations.
    ];

    $property_definition = $this->schemaTypeManager->getProperty($property);

    $field_type_options = $this->getFieldTypesAsOptions();

    // Set property specific field types.
    $property_field_types = [];
    if (isset($property_mappings[$property])) {
      $property_field_types = array_merge($property_field_types, $property_mappings[$property]);
    }

    // Set range include field types.
    $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);
    foreach ($range_includes as $range_include) {
      if (isset($data_type_mappings[$range_include])) {
        $property_field_types = array_merge($property_field_types, $data_type_mappings[$range_include]);
      }
    }

    // Set a default field type.
    if (!$property_field_types) {
      $property_field_types[] = 'entity_reference';
    }

    // Get property options for property field types.
    $property_options = [];
    foreach ($property_field_types as $field_type) {
      if (isset($field_type_options[$field_type])) {
        $property_options[$field_type] = $field_type_options[$field_type];
      }
    }

    return $property_options;
  }

}
