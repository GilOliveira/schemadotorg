<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Schema.org entity type builder service.
 */
class SchemaDotOrgEntityTypeBuilder implements SchemaDotOrgEntityTypeBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    FieldTypePluginManagerInterface $field_type_plugin_manager,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $display_repository;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->schemaNames = $schema_names;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeVocabularyId($type) {
    // The field suffix for type vocabularies needs to be hardcode because
    // type vocabularies are created when the module is installed.
    // @see \Drupal\schemadotorg\SchemaDotOrgInstaller::updateTypeVocabularies
    return 'schema_' . $this->schemaNames->camelCaseToSnakeCase($type);
  }

  /**
   * {@inheritdoc}
   */
  public function createTypeVocabulary($type) {
    $type_definition = $this->schemaTypeManager->getType($type);

    // Create vocabulary.
    $vocabulary_id = $this->getTypeVocabularyId($type);
    $vocabulary_name = 'Schema.org: ' . $type_definition['drupal_label'];

    /** @var \Drupal\taxonomy\VocabularyStorage $vocabulary_storage */
    $vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabulary = $vocabulary_storage->load($vocabulary_id);
    if (!$vocabulary) {
      $vocabulary = $vocabulary_storage->create([
        'name' => $vocabulary_name,
        'vid' => $vocabulary_id,
      ]);
      $vocabulary->save();
    }

    // Add 'schema_type' field to the schema type vocabulary.
    $entity_type_id = 'taxonomy_term';
    $bundle = $vocabulary_id;
    $field_name = 'schema_type';
    $field_label = 'Schema.org: Type';

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config_storage */
    $field_storage_config_storage = $this->entityTypeManager->getStorage('field_storage_config');
    if (!$field_storage_config_storage->load($entity_type_id . '.' . $field_name)) {
      $field_storage_config_storage->create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'type' => 'string',
        'settings' => ['max_length' => 255],
      ])->save();
    }

    /** @var \Drupal\field\FieldConfigInterface $field_storage_config */
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    if (!$field_config_storage->load($entity_type_id . '.' . $bundle . '.' . $field_name)) {
      $field_config_storage->create([
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
        'field_name' => $field_name,
        'label' => $field_label,
      ])->save();
    }

    $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle)
      ->setComponent($field_name, ['type' => 'string_textfield'])
      ->save();

    $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle)
      ->setComponent($field_name, ['type' => 'string'])
      ->save();
  }

  /* ************************************************************************ */
  // Field creation methods copied from FieldStorageAddForm.
  // @see \Drupal\field_ui\Form\FieldStorageAddForm
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function addSubtypeFieldToEntity($entity_type_id, $bundle) {
    $field = [
      'machine_name' => $this->schemaNames->getFieldPrefix() . 'type',
      'type' => 'field_ui:entity_reference:taxonomy_term',
      'label' => 'Type',
      'description' => '',
      'unlimited' => NULL,
      'schema_property' => NULL,
    ];
    $this->addFieldToEntity($entity_type_id, $bundle, $field);
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldToEntity($entity_type_id, $bundle, array $field) {
    // Define and document expected default field settings.
    // @see \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm::save
    $field += [
      'machine_name' => NULL,
      'type' => NULL,
      'label' => NULL,
      'description' => '',
      'unlimited' => NULL,
      'schema_property' => NULL,
    ];

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
    $field_storage_config = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->load($entity_type_id . '.' . $field['machine_name']);

    $field_name = $field['machine_name'];
    $field_type = ($field_storage_config) ? $field_storage_config->getType() : $field['type'];
    $field_label = $field['label'];
    $field_description = $field['description'];
    $field_unlimited = $field['unlimited'];
    $schema_property = $field['schema_property'];

    $new_storage_type = !$field_storage_config;
    $existing_storage = !!$field_storage_config;

    if ($field_storage_config) {
      $field_storage_values = array_intersect_key(
        $field_storage_config->toArray(),
        [
          'field_name' => 'field_name',
          'entity_type' => 'entity_type',
          'type' => 'type',
          'cardinality' => 'cardinality',
          'settings' => 'settings',
        ]);
    }
    else {
      $field_storage_values = [
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'type' => $field_type,
        'cardinality' => $field_unlimited ? -1 : 1,
      ];
    }

    $field_values = [
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'label' => $field_label,
      'description' => $field_description,
    ];

    $widget_id = $formatter_id = NULL;
    $widget_settings = $formatter_settings = [];

    // Create new field.
    if ($new_storage_type) {
      // Check if we're dealing with a preconfigured field.
      if (strpos($field_storage_values['type'], 'field_ui:') !== FALSE) {

        [, $field_type, $option_key] = explode(':', $field_storage_values['type'], 3);
        $field_storage_values['type'] = $field_type;

        $field_definition = $this->fieldTypePluginManager->getDefinition($field_type);
        $options = $this->fieldTypePluginManager->getPreconfiguredOptions($field_definition['id']);
        $field_options = $options[$option_key];
        // Merge in preconfigured field storage options.
        if (isset($field_options['field_storage_config'])) {
          foreach (['cardinality', 'settings'] as $key) {
            if (isset($field_options['field_storage_config'][$key])) {
              $field_storage_values[$key] = $field_options['field_storage_config'][$key];
            }
          }
        }

        // Merge in preconfigured field options.
        if (isset($field_options['field_config'])) {
          foreach (['required', 'settings'] as $key) {
            if (isset($field_options['field_config'][$key])) {
              $field_values[$key] = $field_options['field_config'][$key];
            }
          }
        }

        $widget_id = $field_options['entity_form_display']['type'] ?? NULL;
        $widget_settings = $field_options['entity_form_display']['settings'] ?? [];
        $formatter_id = $field_options['entity_view_display']['type'] ?? NULL;
        $formatter_settings = $field_options['entity_view_display']['settings'] ?? [];
      }

      // Create the field storage and field.
      try {
        $this->alterFieldValues(
          $schema_property,
          $field_storage_values,
          $field_values,
          $widget_id,
          $widget_settings,
            $formatter_id,
            $formatter_settings
        );

        $this->entityTypeManager->getStorage('field_storage_config')->create($field_storage_values)->save();
        $field = $this->entityTypeManager->getStorage('field_config')->create($field_values);
        $field->save();

        $this->configureEntityFormDisplay($entity_type_id, $bundle, $field_name, $widget_id, $widget_settings);
        $this->configureEntityViewDisplay($entity_type_id, $bundle, $field_name, $formatter_id, $formatter_settings);
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError($this->t('There was a problem creating field %label: @message', ['%label' => $field_label, '@message' => $e->getMessage()]));
      }
    }

    // Re-use existing field.
    if ($existing_storage) {
      try {
        $this->alterFieldValues(
          $schema_property,
          $field_storage_values,
          $field_values,
          $widget_id,
          $widget_settings,
          $formatter_id,
          $formatter_settings
        );

        $field = $this->entityTypeManager->getStorage('field_config')->create($field_values);
        $field->save();

        $this->configureEntityFormDisplay($entity_type_id, $bundle, $field_name, $widget_id, $widget_settings);
        $this->configureEntityViewDisplay($entity_type_id, $bundle, $field_name, $formatter_id, $formatter_settings);
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError($this->t('There was a problem creating field %label: @message', ['%label' => $field_label, '@message' => $e->getMessage()]));
      }
    }
  }

  /**
   * Configures the field for the default form mode.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $field_name
   *   The field name.
   * @param string|null $widget_id
   *   (optional) The plugin ID of the widget. Defaults to NULL.
   * @param array $widget_settings
   *   (optional) An array of widget settings. Defaults to an empty array.
   */
  protected function configureEntityFormDisplay($entity_type_id, $bundle, $field_name, $widget_id = NULL, array $widget_settings = []) {
    $options = [];
    if ($widget_id) {
      $options['type'] = $widget_id;
      if (!empty($widget_settings)) {
        $options['settings'] = $widget_settings;
      }
    }
    // Make sure the field is displayed in the 'default' form mode (using
    // default widget and settings). It stays hidden for other form modes
    // until it is explicitly configured.
    $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, 'default')
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Configures the field for the default view mode.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $field_name
   *   The field name.
   * @param string|null $formatter_id
   *   (optional) The plugin ID of the formatter. Defaults to NULL.
   * @param array $formatter_settings
   *   (optional) An array of formatter settings. Defaults to an empty array.
   */
  protected function configureEntityViewDisplay($entity_type_id, $bundle, $field_name, $formatter_id = NULL, array $formatter_settings = []) {
    $options = [];
    if ($formatter_id) {
      $options['type'] = $formatter_id;
      if (!empty($formatter_settings)) {
        $options['settings'] = $formatter_settings;
      }
    }
    // Make sure the field is displayed in the 'default' view mode (using
    // default formatter and settings). It stays hidden for other view
    // modes until it is explicitly configured.
    $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle)
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Alter field storage and field values before they are created.
   *
   * @param string $property
   *   The Schema.org property.
   * @param array $field_storage_values
   *   Field storage config values.
   * @param array $field_values
   *   Field config values.
   * @param string $widget_id
   *   The plugin ID of the widget.
   * @param array $widget_settings
   *   An array of widget settings.
   * @param string|null $formatter_id
   *   The plugin ID of the formatter.
   * @param array $formatter_settings
   *   An array of formatter settings.
   */
  protected function alterFieldValues(
    $property,
    array &$field_storage_values,
    array &$field_values,
    &$widget_id,
    array &$widget_settings,
    &$formatter_id,
    array &$formatter_settings
  ) {
    switch ($field_storage_values['type']) {
      case 'entity_reference_revisions':
      case 'entity_reference':
        $target_type = $field_storage_values['settings']['target_type'] ?? 'node';
        switch ($target_type) {
          case 'taxonomy_term':
            if ($field_values['field_name'] === $this->schemaNames->getFieldPrefix() . 'type') {
              $handler = 'schemadotorg_type';
            }
            else {
              $handler = 'schemadotorg_enumeration';
              $widget_id = 'options_select';
            }
            break;

          default:
            $handler = 'schemadotorg_range_includes';
            break;
        }
        $field_values['settings'] = [
          'handler' => $handler,
          'handler_settings' => [
            'target_type' => $target_type,
            'schemadotorg_mapping' => [
              'entity_type' => $field_values['field_name'],
              'bundle' => $field_values['bundle'],
              'field_name' => $field_values['entity_type'],
            ],
          ],
        ];
        break;

      case 'list_string':
        // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager::getSchemaPropertyFieldTypes
        $property_definition = $this->schemaTypeManager->getProperty($property);
        $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);
        foreach ($range_includes as $range_include) {
          $allowed_values_function = 'schemadotorg_allowed_values_' . strtolower($range_include);
          if (function_exists($allowed_values_function)) {
            $field_storage_values['settings'] = [
              'allowed_values' => [],
              'allowed_values_function' => $allowed_values_function,
            ];
            break;
          }
        }
        break;
    }
  }

}
