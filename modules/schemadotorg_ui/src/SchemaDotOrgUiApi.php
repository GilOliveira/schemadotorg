<?php

namespace Drupal\schemadotorg_ui;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org UI field manager.
 */
class SchemaDotOrgUiApi implements SchemaDotOrgUiApiInterface {
  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

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
   * The Schema.org installer service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface
   */
  protected $schemaInstaller;

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
   * SchemaDotOrgCommands constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->schemaNames = $schema_names;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createTypeValidate($entity_type, $schema_type) {
    // Validate entity type.
    $entity_types = $this->getSchemaMappingTypeStorage()->getEntityTypes();
    if (!in_array($entity_type, $entity_types)) {
      $t_args = [
        '@entity_type' => $entity_type,
        '@entity_types' => implode(', ', $entity_types),
      ];
      $message = $this->t("The entity type '@entity_type' is not valid. Please select a entity type (@entity_types).", $t_args);
      throw new \Exception($message);
    }

    // Validate Schema.org type.
    if (!$this->schemaTypeManager->isType($schema_type)) {
      $t_args = ['@schema_type' => $schema_type];
      $message = $this->t("The Schema.org type '@schema_type' is not valid.", $t_args);
      throw new \Exception($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createType($entity_type, $schema_type, array $options = []) {
    $options += [
      'default-properties' => NULL,
      'unlimited-properties' => NULL,
      'subtypes' => NULL,
    ];

    // Get the default bundle for the schema type.
    // Default bundles are only defined for the 'media' and 'user'
    // entity types.
    $bundles = $this->getSchemaMappingTypeStorage()->getDefaultSchemaTypeBundles($entity_type, $schema_type);
    $bundles = $bundles ?: [$this->schemaNames->toDrupalName('types', $schema_type)];
    foreach ($bundles as $bundle) {
      // Create a new Schema.org mapping.
      $mapping = SchemaDotOrgMapping::create([
        'target_entity_type_id' => $entity_type,
        'target_bundle' => $bundle,
        'type' => $schema_type,
      ]);

      /** @var \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm $form_object */
      $form_object = $this->entityTypeManager->getFormObject('schemadotorg_mapping', 'add');

      // Set the Schema.org mapping entity in the form object.
      $form_object->setEntity($mapping);

      // Set properties and settings.
      $custom_properties = [
        'default-properties' => 'setSchemaTypeDefaultProperties',
        'unlimited-properties' => 'setSchemaTypeUnlimitedProperties',
        'subtypes' => 'setSchemaTypeSubtypes',
      ];
      foreach ($custom_properties as $option_name => $method) {
        if (!empty($options[$option_name])) {
          $properties = preg_split('/\s*,\s*/', $options[$option_name]);
          $form_object->$method($properties);
        }
      }

      // Submit the form.
      $form_state = new FormState();
      $this->formBuilder->submitForm($form_object, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTypeValidate($entity_type, $schema_type) {
    $mappings = $this->loadSchemaMappingsByType($entity_type, $schema_type);
    if (empty($mappings)) {
      $t_args = ['@entity_type' => $entity_type, '@schema_type' => $schema_type];
      throw new \Exception($this->t("No Schema.org mapping exists for @schema_type (@entity_type).", $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteType($entity_type, $schema_type, array $options = []) {
    $options += [
      'delete-entity' => FALSE,
      'delete-fields' => FALSE,
    ];

    $mappings = $this->loadSchemaMappingsByType($entity_type, $schema_type);
    foreach ($mappings as $mapping) {
      $target_entity_bundle = $mapping->getTargetEntityBundleEntity();
      if ($options['delete-entity'] && $target_entity_bundle) {
        $target_entity_bundle->delete();
      }
      else {
        if ($options['delete-fields']) {
          $this->deleteFields($mapping);
        }
        $mapping->delete();
      }
    }
  }

  /**
   * Delete fields and field groups associated with Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   A Schema.org mapping.
   */
  protected function deleteFields(SchemaDotOrgMappingInterface $mapping) {
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    /** @var \Drupal\field\FieldStorageConfigStorage $field_storage_config_storage */
    $field_storage_config_storage = $this->entityTypeManager->getStorage('field_storage_config');
    /** @var \Drupal\field\FieldConfigStorage $field_config_storage */
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');

    $base_field_defintions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

    $deleted_fields = [];
    $properties = array_keys($mapping->getSchemaProperties());
    foreach ($properties as $field_name) {
      // Never delete a base field.
      if (isset($base_field_defintions[$field_name])) {
        continue;
      }

      $field_config = $field_config_storage->load($entity_type_id . '.' . $bundle . '.' . $field_name);
      $field_storage_config = $field_storage_config_storage->load($entity_type_id . '.' . $field_name);
      if ($field_storage_config && count($field_storage_config->getBundles()) <= 1) {
        $field_storage_config->delete();
        $deleted_fields[] = $field_name;
      }
      elseif ($field_config) {
        $field_config->delete();
        $deleted_fields[] = $field_name;
      }
    }

    if ($this->moduleHandler->moduleExists('field_group')) {
      $contexts = ['form', 'view'];
      foreach ($contexts as $context) {
        $groups = field_group_info_groups($entity_type_id, $bundle, $context, 'default');
        foreach ($groups as $group) {
          $group->children = array_diff($group->children, $deleted_fields);
          if (empty($group->children)) {
            field_group_delete_field_group($group);
          }
          else {
            field_group_group_save($group);
          }
        }
      }
    }
  }

  /**
   * Gets the Schema.org mapping type storage.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface
   *   The Schema.org mapping type storage.
   */
  protected function getSchemaMappingTypeStorage() {
    return $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
  }

  /**
   * Gets the Schema.org mapping storage.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface
   *   The Schema.org mapping storage.
   */
  protected function getSchemaMappingStorage() {
    return $this->entityTypeManager->getStorage('schemadotorg_mapping');
  }

  /**
   * Gets the Schema.org mappings by Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface[]
   *   Schema.org mapping.
   */
  protected function loadSchemaMappingsByType($entity_type_id, $schema_type) {
    return $this->getSchemaMappingStorage()->loadByProperties([
      'target_entity_type_id' => $entity_type_id,
      'type' => $schema_type,
    ]);
  }

}
