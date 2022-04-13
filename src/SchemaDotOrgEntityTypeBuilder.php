<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field_group\Form\FieldGroupAddForm;

/**
 * Schema.org entity type builder service.
 */
class SchemaDotOrgEntityTypeBuilder implements SchemaDotOrgEntityTypeBuilderInterface {
  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
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
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    FieldTypePluginManagerInterface $field_type_plugin_manager,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->moduleHandler = $module_handler;
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

        $field_storage_config = $this->entityTypeManager->getStorage('field_storage_config')->create($field_storage_values);
        $field_storage_config->save();

        $field = $this->entityTypeManager->getStorage('field_config')->create($field_values);
        $field->save();

        $this->setEntityDisplays(
          $field_values,
          $widget_id,
          $widget_settings,
          $formatter_id,
          $formatter_settings
        );
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

        $this->setEntityDisplays(
          $field_values,
          $widget_id,
          $widget_settings,
          $formatter_id,
          $formatter_settings
        );
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError($this->t('There was a problem creating field %label: @message', ['%label' => $field_label, '@message' => $e->getMessage()]));
      }
    }
  }

  /**
   * Set entity displays for a field.
   *
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
  protected function setEntityDisplays($field_values, $widget_id, $widget_settings, $formatter_id, $formatter_settings) {
    $entity_type_id = $field_values['entity_type'];
    $bundle = $field_values['bundle'];
    $field_name = $field_values['field_name'];

    // Form display.
    $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, 'default');
    $this->setEntityDisplayComponent($form_display, $field_name, $widget_id, $widget_settings);
    $form_display->save();

    // View display.
    $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle);
    $this->setEntityDisplayComponent($view_display, $field_name, $formatter_id, $formatter_settings);
    $view_display->save();
  }

  /**
   * Set entity display component.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   An entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $type
   *   The component's plugin id.
   * @param array $settings
   *   The component's plugin settings.
   */
  protected function setEntityDisplayComponent(EntityDisplayInterface $display, $field_name, $type, array $settings) {
    $options = [];
    if ($type) {
      $options['type'] = $type;
      if (!empty($settings)) {
        $options['settings'] = $settings;
      }
    }

    // Custom weights.
    $entity_type_id = $display->getTargetEntityTypeId();
    switch ($entity_type_id) {
      case 'media':
        $options['weight'] = 10;
        break;
    }

    $display->setComponent($field_name, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityDisplayFieldGroups($entity_type_id, $bundle, $schema_type, array $properties) {
    // Make sure the field group module is enabled.
    if (!$this->moduleHandler->moduleExists('field_group')) {
      return;
    }

    $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle);
    $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle);
    foreach ($properties as $field_name => $property) {
      $this->setEntityDisplayFieldGroup($form_display, $field_name, $schema_type, $property);
      $this->setEntityDisplayFieldGroup($view_display, $field_name, $schema_type, $property);
    }
    $form_display->save();
    $view_display->save();
  }

  /**
   * Set entity display field groups for a Schema.org property.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   An entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $schema_property
   *   The field name's associated Schema.org property.
   *
   * @see field_group_group_save()
   * @see field_group_field_overview_submit()
   * @see \Drupal\field_group\Form\FieldGroupAddForm::submitForm
   */
  protected function setEntityDisplayFieldGroup(EntityDisplayInterface $display, $field_name, $schema_type, $schema_property) {
    // Make sure the field component exists.
    if (!$display->getComponent($field_name)) {
      return;
    }

    $entity_type_id = $display->getTargetEntityTypeId();

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $default_field_groups = $mapping_type_storage->getDefaultFieldGroups($entity_type_id);
    $default_format_type = $mapping_type_storage->getDefaultFieldGroupFormatType($entity_type_id, $display);
    $default_format_settings = $mapping_type_storage->getDefaultFieldGroupFormatSettings($entity_type_id, $display);
    if (empty($default_field_groups) || empty($default_format_type)) {
      return;
    }

    $group_weight = 0;
    $group_name = NULL;
    $group_label = NULL;
    $field_weight = NULL;
    $index = 0 - count($default_field_groups);
    foreach ($default_field_groups as $default_field_group_name => $default_field_group) {
      $properties = array_flip($default_field_group['properties']);
      if (isset($properties[$schema_property])) {
        $group_name = $default_field_group_name;
        $group_label = $default_field_group['label'];
        $group_weight = $index;
        $field_weight = $properties[$schema_property];
        break;
      }
      $index++;
    }

    // Automatically generate a default catch all field group for
    // the Schema.org type.
    if (!$group_name) {
      // But don't generate a group for default fields.
      $base_field_names = $mapping_type_storage->getBaseFieldNames($entity_type_id);
      if (isset($base_field_names[$field_name])) {
        return;
      }

      $group_name = $this->schemaNames->toDrupalName('types', $schema_type);
      $group_label = $this->schemaNames->toDrupalLabel('types', $schema_type);
      $field_weight = $index;
    }

    // Prefix group name.
    $group_name = FieldGroupAddForm::GROUP_PREFIX . $group_name;

    // Get existing groups.
    $group = $display->getThirdPartySetting('field_group', $group_name);
    if (!$group) {
      $group = [
        'label' => $group_label,
        'children' => [],
        'parent_name' => '',
        'weight' => $group_weight,
        'format_type' => $default_format_type,
        'format_settings' => $default_format_settings,
        'region' => 'content',
      ];
    }

    // Append the field to the children.
    $group['children'][] = $field_name;
    $group['children'] = array_unique($group['children']);

    // Set field group in the entity display.
    $display->setThirdPartySetting('field_group', $group_name, $group);

    // Set field component's weight.
    $component = $display->getComponent($field_name);
    $component['weight'] = $field_weight;
    $display->setComponent($field_name, $component);
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

        // Field values settings.
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

          case 'media':
            $handler = 'schemadotorg_range_includes';
            if ($this->moduleHandler->moduleExists('media_library')) {
              $widget_id = 'media_library_widget';
            }
            break;

          case 'paragraph':
            $widget_id = 'paragraphs';
            $handler = 'schemadotorg_range_includes';
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
