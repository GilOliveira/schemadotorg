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
      // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::alterFieldValues
      $allowed_values_function = 'schemadotorg_allowed_values_' . strtolower($range_include);
      if (function_exists($allowed_values_function)) {
        $field_types['list_string'] = 'list_string';
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
    if (!$field_types) {
      $entity_reference_field_type = $this->getDefaultEntityReferenceFieldType($range_includes);
      $field_types += [
        $entity_reference_field_type => $entity_reference_field_type,
        'string' => 'string',
      ];
    }

    return $field_types;
  }

  /**
   * Gets the entity reference field type based on an array Schema.org types.
   *
   * @param array $types
   *   Schema.org types, extracted from a property's range includes.
   *
   * @return string
   *   The entity reference field type.
   */
  protected function getDefaultEntityReferenceFieldType(array $types) {
    $sub_types = $this->schemaTypeManager->getAllSubTypes($types);
    if (empty($sub_types)) {
      return 'field_ui:entity_reference:node';
    }

    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    $entity_ids = $schemadotorg_mapping_storage->getQuery()
      ->condition('type', $sub_types, 'IN')
      ->execute();
    if (empty($entity_ids)) {
      return 'field_ui:entity_reference:node';
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $schemadotorg_mappings */
    $schemadotorg_mappings = $schemadotorg_mapping_storage->loadMultiple($entity_ids);

    // Define the default order for found entity types.
    $entity_types = [
      'paragraph' => NULL,
      'block_content' => NULL,
      'media' => NULL,
      'node' => NULL,
      'user' => NULL,
    ];
    foreach ($schemadotorg_mappings as $schemadotorg_mapping) {
      $entity_types[$schemadotorg_mapping->getTargetEntityTypeId()] = $schemadotorg_mapping->getTargetEntityTypeId();
    }

    // Filter the entity types so that only found entity types are included.
    $entity_types = array_filter($entity_types);

    // Get first entity type.
    $entity_type = reset($entity_types);

    return ($entity_type === 'paragraph')
      ? 'field_ui:entity_reference_revisions:paragraph'
      : "field_ui:entity_reference:$entity_type";
  }

}

