<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Schema.org entity type manager.
 */
class SchemaDotOrgEntityTypeManager implements SchemaDotOrgEntityTypeManagerInterface {

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
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * Get entity types that implement Schema.org.
   *
   * @return array
   *   Entity types that implement Schema.org.
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
   * Get an entity type's base fields names.
   *
   * @param string $entity_type_id
   *   An entity type.
   *
   * @return array|string[]
   *   An entity type's base fields names.
   */
  public function getBaseFieldNames($entity_type_id) {
    $base_fields_names = [
      'node' => [
        'uuid',
        'revision_uid',
        'uid',
        'title',
        'created',
        'changed',
        'promote',
        'sticky',
        'path',
      ],
      'user' => [
        'uuid',
        'name',
        'mail',
      ],
    ];
    return $base_fields_names[$entity_type_id] ?? [];
  }

  /**
   * Get field types for Schema.org property.
   *
   * @param string $property
   *   A Schema.org property.
   *
   * @return array
   *   Field types for Schema.org property.
   */
  public function getSchemaPropertyFieldTypes($property) {
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

    // Set property specific field types.
    $field_types = [];
    if (isset($property_mappings[$property])) {
      $field_types = array_merge($field_types, $property_mappings[$property]);
    }

    // Set range include field types.
    $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);
    foreach ($range_includes as $range_include) {
      if (isset($data_type_mappings[$range_include])) {
        $field_types = array_merge($field_types, $data_type_mappings[$range_include]);
      }
    }

    // Set a default field type.
    if (!$field_types) {
      $field_types[] = 'entity_reference';
    }

    return $field_types;
  }

}
