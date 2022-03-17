<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Schema.org entity type manager.
 */
class SchemaDotOrgEntityTypeManager implements SchemaDotOrgEntityTypeManagerInterface {
  use StringTranslationTrait;

  /**
   * The Schema.org config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgEntityTypeManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->config = $config->get('schemadotorg.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    return array_keys($this->config->get('entity_types'));
  }

  /**
   * Get default Schema.org type for an entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string|null
   *   The default Schema.org type for an entity type and bundle.
   */
  public function getDefaultSchemaType($entity_type_id, $bundle) {
    return $this->config->get("entity_types.$entity_type_id.default_schema_types.$bundle");
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldNames($entity_type_id) {
    return $this->config->get("entity_types.$entity_type_id.base_fields") ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCommonSchemaTypes($entity_type_id) {
    return $this->config->get("entity_types.$entity_type_id.schema_types") ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyDefaults($entity_type_id) {
    return $this->config->get("entity_types.$entity_type_id.default_schema_properties");
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyFieldTypes($property) {
    $property_mappings = $this->config->get('schema_properties.default_field_types');
    $type_mappings = $this->config->get('schema_types.default_field_types');

    $property_definition = $this->schemaTypeManager->getProperty($property);

    // Set property specific field types.
    $field_types = [];
    if (isset($property_mappings[$property])) {
      $field_types += array_combine($property_mappings[$property], $property_mappings[$property]);
    }

    // Set range include field types.
    $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);

    // Prioritize enumerations and types (not data types).
    foreach ($range_includes as $range_include) {
      if ($this->schemaTypeManager->isEnumerationType($range_include)) {
        $field_types['field_ui:entity_reference:taxonomy_term'] = 'field_ui:entity_reference:taxonomy_term';
        break;
      }
      if (isset($type_mappings[$range_include]) && !$this->schemaTypeManager->isDataType($range_include)) {
        $field_types += array_combine($type_mappings[$range_include], $type_mappings[$range_include]);
      }
    }

    // Set default data type related field types.
    if (!$field_types) {
      foreach ($range_includes as $range_include) {
        if (isset($type_mappings[$range_include]) && $this->schemaTypeManager->isDataType($range_include)) {
          $field_types += array_combine($type_mappings[$range_include], $type_mappings[$range_include]);
        }
      }
    }

    // Set a default field type to an entity reference and string (a.k.a. name).
    // @todo Default to the relevant entity type.
    if (!$field_types) {
      $field_types += [
        'field_ui:entity_reference:node' => 'field_ui:entity_reference:node',
        'string' => 'string',
      ];
    }

    return $field_types;
  }

}
