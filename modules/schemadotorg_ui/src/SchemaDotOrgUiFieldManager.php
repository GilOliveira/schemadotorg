<?php

namespace Drupal\schemadotorg_ui;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org UI field manager.
 */
class SchemaDotOrgUiFieldManager implements SchemaDotOrgUiFieldManagerInterface {
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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgUiFieldManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    FieldTypePluginManagerInterface $field_type_plugin_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->config = $config->get('schemadotorg.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * Determine if a field exists for the current entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $field_name
   *   A field name.
   *
   * @return bool
   *   TRUE if a field exists for the current entity.
   */
  public function fieldExists($entity_type_id, $bundle, $field_name) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    return isset($field_definitions[$field_name]);
  }

  /**
   * Determine if a field storage exists for the current entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   A field name.
   *
   * @return bool
   *   TRUE if a field storage exists for the current entity.
   */
  public function fieldStorageExists($entity_type_id, $field_name) {
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    return isset($field_storage_definitions[$field_name]);
  }

  /**
   * Gets a field's label from an existing field instance.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   A field name.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   A field's label from an existing field instance.
   */
  public function getFieldLabel($entity_type_id, $field_name) {
    $field_ids = $this->entityTypeManager->getStorage('field_config')->getQuery()
      ->condition('entity_type', $entity_type_id)
      ->condition('field_name', $field_name)
      ->execute();
    if ($field_ids) {
      $field_config = $this->entityTypeManager->getStorage('field_config')
        ->load(reset($field_ids));
      return $field_config->label();
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyFieldTypeOptions($property) {
    $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $recommended_field_types = $this->getSchemaPropertyFieldTypes($property);
    $recommended_category = (string) $this->t('Recommended');

    $options = [$recommended_category => []];

    // Collecting found field type to ensure the field type is installed.
    $grouped_definitions = $this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions());
    foreach ($grouped_definitions as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        if (isset($recommended_field_types[$name])) {
          $options[$recommended_category][$name] = $field_type['label'];
        }
        else {
          $options[$category][$name] = $field_type['label'];
        }
      }
    }
    if (empty($options[$recommended_category])) {
      unset($options[$recommended_category]);
    }
    else {
      // @see https://stackoverflow.com/questions/348410/sort-an-array-by-keys-based-on-another-array#answer-9098675
      $recommended_field_types = array_intersect_key($recommended_field_types, $options[$recommended_category]);
      $options[$recommended_category] = array_replace($recommended_field_types, $options[$recommended_category]);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitionsOptions($entity_type_id, $bundle) {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    $field_definitions = array_diff_key(
      $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle),
      $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id)
    );

    $options = [];
    foreach ($field_definitions as $field_definition) {
      $options[$field_definition->getName()] = $this->t('@field (@type)', [
        '@type' => $field_types[$field_definition->getType()]['label'],
        '@field' => $field_definition->getLabel(),
      ]);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldOptions($entity_type_id, $bundle) {
    $options = [];
    $options[static::ADD_FIELD] = $this->t('Add a new field…');

    $field_definition_options = $this->getFieldDefinitionsOptions($entity_type_id, $bundle);
    if ($field_definition_options) {
      $options[(string) $this->t('Fields')] = $field_definition_options;
    }

    $base_field_definition_options = $this->getBaseFieldDefinitionsOptions($entity_type_id, $bundle);
    if ($base_field_definition_options) {
      $options[(string) $this->t('Base fields')] = $base_field_definition_options;
    }

    $existing_field_storage_options = $this->getExistingFieldStorageOptions($entity_type_id, $bundle);
    if ($existing_field_storage_options) {
      $options[(string) $this->t('Existing fields')] = $existing_field_storage_options;
    }
    return $options;
  }

  /**
   * Gets base fields as options.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   Base fields as options.
   */
  protected function getBaseFieldDefinitionsOptions($entity_type_id, $bundle) {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $options = [];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $base_field_names = $mapping_type_storage->getBaseFieldNames($entity_type_id);
    if ($base_field_names) {
      foreach ($base_field_names as $field_name) {
        if (isset($field_definitions[$field_name])) {
          $field_definition = $field_definitions[$field_name];
          $options[$field_definition->getName()] = $this->t('@field (@type)', [
            '@type' => $field_types[$field_definition->getType()]['label'],
            '@field' => $field_definition->getLabel(),
          ]);
        }
      }
    }
    else {
      foreach ($field_definitions as $field_definition) {
        $options[$field_definition->getName()] = $this->t('@field (@type)', [
          '@type' => $field_types[$field_definition->getType()]['label'],
          '@field' => $field_definition->getLabel(),
        ]);
      }
    }

    return $options;
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of existing field storages keyed by name.
   *
   * @see \Drupal\field_ui\Form\FieldStorageAddForm::getExistingFieldStorageOptions
   */
  protected function getExistingFieldStorageOptions($entity_type_id, $bundle) {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    // Load the field_storages and build the list of options.
    $options = [];
    foreach ($this->entityFieldManager->getFieldStorageDefinitions($entity_type_id) as $field_name => $field_storage) {
      // Do not show:
      // - non-configurable field storages,
      // - locked field storages,
      // - field storages that should not be added via user interface,
      // - field storages that already have a field in the bundle.
      $field_type = $field_storage->getType();
      if ($field_storage instanceof FieldStorageConfigInterface
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])
        && !in_array($bundle, $field_storage->getBundles(), TRUE)) {
        $options[$field_name] = $this->t('@field (@type)', [
          '@type' => $field_types[$field_type]['label'],
          '@field' => $field_name,
        ]);
      }
    }
    asort($options);

    return $options;
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
