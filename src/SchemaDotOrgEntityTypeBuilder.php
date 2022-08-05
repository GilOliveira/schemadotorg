<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org entity display builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface
   */
  protected $schemaEntityDisplayBuilder;

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
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface $schema_entity_display_builder
   *   The Schema.org entity display builder.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    FieldTypePluginManagerInterface $field_type_plugin_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager,
    SchemaDotOrgEntityDisplayBuilderInterface $schema_entity_display_builder
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $display_repository;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->schemaTypeManager = $schema_type_manager;
    $this->schemaEntityDisplayBuilder = $schema_entity_display_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function addEntityBundle($schema_type, $entity_type_id, array $values) {
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);

    // Get bundle entity values and map id and label keys.
    // (i.e, A node's label is saved in the database as its title)
    $keys = ['id', 'label'];
    foreach ($keys as $key) {
      $key_name = $entity_type_definition->getKey($key);
      if ($key_name !== $key) {
        $values[$key_name] = $values[$key];
        unset($values[$key]);
      }
    }

    // Alter Schema.org bundle entity values.
    $this->moduleHandler->invokeAll('schemadotorg_bundle_entity_alter', [$schema_type, $entity_type_id, &$values]);

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $bundle_entity_storage */
    $bundle_entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $bundle_entity = $bundle_entity_storage->create($values);
    $bundle_entity->schemaDotOrgType = $schema_type;
    $bundle_entity->save();
    return $bundle_entity;
  }

  /* ************************************************************************ */
  // Field creation methods copied from FieldStorageAddForm.
  // @see \Drupal\field_ui\Form\FieldStorageAddForm
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function addFieldToEntity($entity_type_id, $bundle, array $field) {
    // Define and document expected default field settings.
    // @see \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm::buildSchemaPropertyFieldForm
    $field += [
      'machine_name' => NULL,
      'type' => NULL,
      'label' => NULL,
      'description' => '',
      'unlimited' => NULL,
      'allowed_values' => [],
      'schema_type' => NULL,
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
    $field_allowed_values = $field['allowed_values'];
    $schema_type = $field['schema_type'];
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
        'allowed_values' => $field_allowed_values,
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
          foreach (['settings'] as $key) {
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
          $schema_type,
          $schema_property,
          $field_storage_values,
          $field_values,
          $widget_id,
          $widget_settings,
            $formatter_id,
            $formatter_settings
        );

        $field_storage_config = $this->entityTypeManager->getStorage('field_storage_config')->create($field_storage_values);
        $field_storage_config->schemaType = $schema_type;
        $field_storage_config->schemaProperty = $schema_property;
        $field_storage_config->save();

        $field = $this->entityTypeManager->getStorage('field_config')->create($field_values);
        $field->schemaDotOrgType = $schema_type;
        $field->schemaDotOrgProperty = $schema_property;
        $field->save();

        $this->schemaEntityDisplayBuilder->setDisplays(
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
          $schema_type,
          $schema_property,
          $field_storage_values,
          $field_values,
          $widget_id,
          $widget_settings,
          $formatter_id,
          $formatter_settings
        );

        $field = $this->entityTypeManager->getStorage('field_config')->create($field_values);
        $field->schemaDotOrgType = $schema_type;
        $field->schemaDotOrgProperty = $schema_property;
        $field->save();

        $this->schemaEntityDisplayBuilder->setDisplays(
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
   * Alter field storage and field values before they are created.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
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
    $schema_type,
    $schema_property,
    array &$field_storage_values,
    array &$field_values,
    &$widget_id,
    array &$widget_settings,
    &$formatter_id,
    array &$formatter_settings
  ) {
    $this->copyExistingFieldValues(
      $field_values,
      $widget_id,
      $widget_settings,
      $formatter_id,
      $formatter_settings
    );

    $this->setDefaultFieldValues(
      $schema_type,
      $schema_property,
      $field_storage_values,
      $field_values,
      $widget_id,
      $widget_settings,
      $formatter_id,
      $formatter_settings
    );

    $this->moduleHandler->invokeAll('schemadotorg_property_field_alter', [
      $schema_type,
      $schema_property,
      &$field_storage_values,
      &$field_values,
      &$widget_id,
      &$widget_settings,
      &$formatter_id,
      &$formatter_settings,
    ]);
  }

  /**
   * Copy existing field, form, and view settings.
   *
   * Issue #2717319: Provide better default configuration when re-using
   * an existing field.
   * https://www.drupal.org/project/drupal/issues/2717319
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
  protected function copyExistingFieldValues(
    array &$field_values,
    &$widget_id,
    array &$widget_settings,
    &$formatter_id,
    array &$formatter_settings
  ) {
    // Get the entity type id and field.
    $entity_type_id = $field_values['entity_type'];
    $field_name = $field_values['field_name'];

    // Look for existing field instance and copy field, form, and view settings.
    /** @var \Drupal\field\FieldConfigStorage $field_config_storage */
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    $existing_field_configs = $field_config_storage->loadByProperties([
      'entity_type' => $entity_type_id,
      'field_name' => $field_name,
    ]);
    if (!$existing_field_configs) {
      return;
    }

    /** @var \Drupal\field\FieldConfigInterface $existing_field_config */
    $existing_field_config = reset($existing_field_configs);
    $existing_bundle = $existing_field_config->getTargetBundle();

    // Set field properties.
    $field_property_names = [
      'required',
      'default_value',
      'default_value_callback',
      'settings',
    ];
    foreach ($field_property_names as $field_property_name) {
      $field_values[$field_property_name] = $existing_field_config->get($field_property_name);
    }
    // Only set the description if a custom one is not set.
    if (empty($field_values['description'])) {
      $field_values['description'] = $existing_field_config->get('description');
    }

    // Set widget id and settings from existing form display.
    $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $existing_bundle);
    $existing_form_component = $form_display->getComponent($field_name);
    if ($existing_form_component) {
      $widget_id = $existing_form_component['type'];
      $widget_settings = $existing_form_component['settings'];
    }

    // Set formatter id and settings from existing view display.
    $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $existing_bundle);
    $existing_view_component = $view_display->getComponent($field_name);
    if ($existing_view_component) {
      $formatter_id = $existing_view_component['type'];
      $formatter_settings = $existing_view_component['settings'];
    }
  }

  /**
   * Default default field, form, and view settings.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
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
  protected function setDefaultFieldValues(
    $schema_type,
    $schema_property,
    array &$field_storage_values,
    array &$field_values,
    &$widget_id,
    array &$widget_settings,
    &$formatter_id,
    array &$formatter_settings
  ) {
    switch ($field_storage_values['type']) {
      case 'entity_reference':
      case 'entity_reference_revisions':
        /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
        $mapping_storage = $this->entityTypeManager
          ->getStorage('schemadotorg_mapping');

        $target_type = $field_storage_values['settings']['target_type'] ?? 'node';
        $target_bundles = $mapping_storage->getSchemaPropertyTargetBundles($target_type, $schema_type, $schema_property);
        if (!$target_bundles) {
          return;
        }

        $handler_settings = [];
        $handler_settings['target_bundles'] = $target_bundles;
        switch ($target_type) {
          case 'media':
            // Widget.
            if ($this->moduleHandler->moduleExists('media_library')) {
              $widget_id = 'media_library_widget';
            }
            // Formatter.
            $formatter_id = 'entity_reference_entity_view';
            break;

          case 'node':
            // Widget.
            if ($this->moduleHandler->moduleExists('entity_browser')
              && $this->moduleHandler->moduleExists('content_browser')) {
              $widget_id = 'entity_browser_entity_reference';
              $widget_settings = [
                'entity_browser' => 'browse_content',
                'field_widget_display' => 'label',
                'field_widget_edit' => TRUE,
                'field_widget_remove' => TRUE,
                'field_widget_replace' => TRUE,
                'open' => FALSE,
                'field_widget_display_settings' => [],
                'selection_mode' => 'selection_append',
              ];
            }
            break;

        }
        $field_values['settings'] = [
          'handler' => 'default:' . $target_type,
          'handler_settings' => $handler_settings,
        ];
        break;

      case 'integer':
      case 'float':
      case 'decimal':
        $unit_plural = $this->schemaTypeManager->getPropertyUnit($schema_property, 0);
        if ($unit_plural) {
          $unit_singular = $this->schemaTypeManager->getPropertyUnit($schema_property, 1);
          if ((string) $unit_singular != (string) $unit_plural) {
            $field_values['settings']['suffix'] = ' ' . $unit_singular . '| ' . $unit_plural;
          }
          else {
            $field_values['settings']['suffix'] = ' ' . $unit_singular;
          }
        }
        break;

      case 'list_string':
        if (!empty($field_storage_values['allowed_values'])) {
          $field_storage_values['settings'] = [
            'allowed_values' => $field_storage_values['allowed_values'],
            'allowed_values_function' => '',
          ];
          unset($field_storage_values['allowed_values']);
        }
        else {
          // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager::getSchemaPropertyFieldTypes
          $property_definition = $this->schemaTypeManager->getProperty($schema_property);
          $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);
          foreach ($range_includes as $range_include) {
            // Set allowed values function if it exists.
            // @see schemadotorg.allowed_values.inc
            // @see schemadotorg_allowed_values_country()
            // @see schemadotorg_allowed_values_language()
            $allowed_values_function = 'schemadotorg_allowed_values_' . strtolower($range_include);
            if (function_exists($allowed_values_function)) {
              $field_storage_values['settings'] = [
                'allowed_values' => [],
                'allowed_values_function' => $allowed_values_function,
              ];
              break;
            }

            // Copy enumeration values into allowed values.
            if ($this->schemaTypeManager->isEnumerationType($range_include)) {
              $allowed_values = $this->schemaTypeManager->getTypeChildrenAsOptions($range_include);
              // Append 'Other' to GenderType, which is Male or Female, to be
              // more inclusive.
              if ($range_include === 'GenderType') {
                $allowed_values['Unspecified'] = 'Unspecified';
              }
              $field_storage_values['settings'] = [
                'allowed_values' => $allowed_values,
                'allowed_values_function' => '',
              ];
              break;
            }
          }
        }
        break;
    }
  }

}
