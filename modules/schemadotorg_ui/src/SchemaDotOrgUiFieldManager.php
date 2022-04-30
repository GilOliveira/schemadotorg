<?php

namespace Drupal\schemadotorg_ui;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
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
   * {@inheritdoc}
   */
  public function fieldExists($entity_type_id, $bundle, $field_name) {
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      return FALSE;
    }

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    return isset($field_definitions[$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldStorageExists($entity_type_id, $field_name) {
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      return FALSE;
    }

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    return isset($field_storage_definitions[$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getField($entity_type_id, $field_name) {
    $field_ids = $this->entityTypeManager->getStorage('field_config')->getQuery()
      ->condition('entity_type', $entity_type_id)
      ->condition('field_name', $field_name)
      ->execute();
    if ($field_ids) {
      return $this->entityTypeManager->getStorage('field_config')
        ->load(reset($field_ids));
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyFieldTypeOptions($property) {
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
   * Gets the current entity's fields as options.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   The current entity's fields as options.
   */
  protected function getFieldDefinitionsOptions($entity_type_id, $bundle) {
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

    $base_field_names = $this->getMappingTypeStorage()->getBaseFieldNames($entity_type_id);
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
    // Set range includes.
    $property_definition = $this->schemaTypeManager->getProperty($property);
    $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);
    // Remove generic Schema.org types from range includes.
    $specific_range_includes = $range_includes;
    unset(
      $specific_range_includes['Thing'],
      $specific_range_includes['CreativeWork'],
      $specific_range_includes['Intangible'],
    );

    // Set default entity reference type and field type.
    $entity_reference_entity_type = $this->getDefaultEntityReferenceEntityType($range_includes);
    $entity_reference_field_type = $this->getDefaultEntityReferenceFieldType($entity_reference_entity_type);

    $field_types = [];

    // Check specific Schema.org type entity reference target bundles
    // (a.k.a. range_includes) exist.
    $entity_reference_target_bundles = $this->getMappingStorage()->getRangeIncludesTargetBundles($entity_reference_entity_type, $specific_range_includes);
    if ($entity_reference_target_bundles) {
      $field_types[$entity_reference_field_type] = $entity_reference_field_type;
    }

    // Set Schema.org property specific field types.
    $property_mappings = $this->getFieldTypeMapping('properties');
    if (isset($property_mappings[$property])) {
      $field_types += $property_mappings[$property];
    }

    // Check for Schema.org enumerations and Drupal allowed values.
    if (empty($field_types)) {
      foreach ($range_includes as $range_include) {
        if ($this->schemaTypeManager->isEnumerationType($range_include)) {
          $field_types['field_ui:entity_reference:taxonomy_term'] = 'field_ui:entity_reference:taxonomy_term';
          return $field_types;
        }
        // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::alterFieldValues
        $allowed_values_function = 'schemadotorg_allowed_values_' . strtolower($range_include);
        if (function_exists($allowed_values_function)) {
          $field_types['list_string'] = 'list_string';
          return $field_types;
        }
      }
    }

    // Check Schema.org type mappings.
    if (empty($field_types)) {
      $type_mappings = $this->getFieldTypeMapping('types');
      foreach ($type_mappings as $type => $type_mapping) {
        if (isset($range_includes[$type])) {
          $field_types += $type_mapping;
        }
      }
    }

    // Check generic Schema.org type entity reference target bundles
    // (a.k.a. range_includes) exist.
    if ($range_includes !== $specific_range_includes) {
      $generic_range_includes = array_diff_key($range_includes, $specific_range_includes);
      $entity_reference_target_bundles = $this->getMappingStorage()->getRangeIncludesTargetBundles($entity_reference_entity_type, $generic_range_includes);
      if ($entity_reference_target_bundles) {
        $field_types[$entity_reference_field_type] = $entity_reference_field_type;
      }
    }

    // Set default field types to string and entity reference.
    if (empty($field_types)) {
      $field_types += [
        'string' => 'string',
        $entity_reference_field_type => $entity_reference_field_type,
      ];
    }

    return $field_types;
  }

  /**
   * Get Schema.org type or property field type mapping.
   *
   * @param string $table
   *   Types or properties table name.
   *
   * @return array
   *   Schema.org type or property field type mapping.
   */
  protected function getFieldTypeMapping($table) {
    $mapping = &drupal_static(__FUNCTION__ . '_' . $table);
    if (!isset($mapping)) {
      $field_type_definitions = $this->fieldTypePluginManager->getUiDefinitions();
      $name = SchemaDotOrgNamesInterface::DEFAULT_PREFIX . $table . '.default_field_types';
      $mapping = $this->config->get($name);
      foreach ($mapping as $id => $field_types) {
        $mapping[$id] = array_combine($field_types, $field_types);
        foreach ($mapping[$id] as $field_type) {
          if (!isset($field_type_definitions[$field_type])) {
            unset($mapping[$id][$field_type]);
          }
        }
      }
    }
    return $mapping;
  }

  /**
   * Get default entity reference field type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   Default entity reference field type.
   */
  protected function getDefaultEntityReferenceFieldType($entity_type_id) {
    return ($entity_type_id === 'paragraph')
      ? 'field_ui:entity_reference_revisions:paragraph'
      : "field_ui:entity_reference:$entity_type_id";
  }

  /**
   * Gets the entity reference entity type based on an array Schema.org types.
   *
   * @param array $types
   *   Schema.org types, extracted from a property's range includes.
   *
   * @return string
   *   The entity reference entity type.
   */
  protected function getDefaultEntityReferenceEntityType(array $types) {
    // Remove 'Thing' from $types because it is too generic.
    $types = array_combine($types, $types);
    unset($types['Thing']);

    $schemadotorg_mapping_storage = $this->getMappingStorage();

    // Loop through the types to respect the ordering and prioritization.
    foreach ($types as $type) {
      $sub_types = $this->schemaTypeManager->getAllSubTypes([$type]);
      if (empty($sub_types)) {
        continue;
      }

      $entity_ids = $schemadotorg_mapping_storage->getQuery()
        ->condition('type', $sub_types, 'IN')
        ->execute();
      if (empty($entity_ids)) {
        continue;
      }

      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $schemadotorg_mappings */
      $schemadotorg_mappings = $schemadotorg_mapping_storage->loadMultiple($entity_ids);

      // Define the default order for found entity types.
      $entity_types = [
        'paragraph' => NULL,
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
      return reset($entity_types);
    }

    return 'node';
  }

  /**
   * Gets Schema.org mapping storage.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface
   *   The Schema.org mapping storage.
   */
  protected function getMappingStorage() {
    return $this->entityTypeManager->getStorage('schemadotorg_mapping');
  }

  /**
   * Gets Schema.org mapping type storage.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface
   *   The Schema.org mapping type storage.
   */
  protected function getMappingTypeStorage() {
    return $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
  }

}
