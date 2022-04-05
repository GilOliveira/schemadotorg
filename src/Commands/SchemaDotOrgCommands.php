<?php

namespace Drupal\schemadotorg\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\SchemaDotOrgInstallerInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Drush commands.
 */
class SchemaDotOrgCommands extends DrushCommands {

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
   * @param \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $schema_installer
   *   The Schema.org installer service.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgInstallerInterface $schema_installer,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    parent::__construct();
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaInstaller = $schema_installer;
    $this->schemaNames = $schema_names;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /* ************************************************************************ */
  // Update schema.
  /* ************************************************************************ */

  /**
   * Update Schema.org data and taxonomy.
   *
   * @command schemadotorg:update-schema
   *
   * @usage schemadotorg:update-schema
   *
   * @aliases soup
   */
  public function update() {
    if (!$this->io()->confirm($this->t('Are you sure you want to update Schema.org data and taxonomy?'))) {
      throw new UserAbortException();
    }

    $this->schemaInstaller->install();
    $this->output()->writeln($this->t('Schema.org data and taxonomy updated.'));
  }

  /* ************************************************************************ */
  // Create type.
  /* ************************************************************************ */

  /**
   * Validates the entity type and Schema.org type to be created.
   *
   * @hook validate schemadotorg:create-type
   */
  public function createTypeValidate(CommandData $commandData) {
    if (!$this->moduleHandler->moduleExists('schemadotorg_ui')) {
      throw new \Exception($this->t('The Schema.org UI module must be enabled to create Schema.org types.'));
    }

    $arguments = $commandData->getArgsWithoutAppName();
    $types = $arguments['types'] ?? [];
    if (empty($types)) {
      throw new \Exception(dt('Schema.org types are required.'));
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $entity_types = $mapping_type_storage->getEntityTypes();

    // Validate mapping type, entity type, Schema.org type.
    foreach ($types as $type) {
      // Validate mapping type.
      if (strpos($type, ':') === FALSE) {
        $t_args = ['@type' => $type];
        $message = $this->t("The Schema.org mapping type '@type' is not valid. A Schema.org type must be defined with an entity type and Schema.org type delimited using a colon (:).", $t_args);
        throw new \Exception($message);
      }
      [$entity_type_id, $schema_type] = explode(':', $type);

      // Validate entity type.
      if (!in_array($entity_type_id, $entity_types)) {
        $t_args = [
          '@entity_type' => $entity_type_id,
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
  }

  /**
   * Create Schema.org types.
   *
   * @param array $types
   *   A list of Schema.org mapping types.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command schemadotorg:create-type
   *
   * @usage drush schemadotorg:create-type paragraph:ContactPoint paragraph:PostalAddress
   * @usage drush schemadotorg:create-type media:AudioObject media:DataDownload media:ImageObject media:VideoObject
   * @usage drush schemadotorg:create-type user:Person
   * @usage drush schemadotorg:create-type node:Person node:Organization node:Place node:Event node:CreativeWork
   * @usage drush schemadotorg:create-type --default-properties=longitude,latitude node:Place
   * @usage drush schemadotorg:create-type --subtypes=Organization node:Organization
   *
   * @option default-properties A comma delimited list of additional default Schema.org properties.
   * @option unlimited-properties A comma delimited list of additional unlimited Schema.org properties.
   * @option subtypes A comma delimited list of Schema.org types that should support subtyping.
   *
   * @aliases socr
   */
  public function createType(array $types, array $options = ['default-properties' => NULL, 'unlimited-properties' => NULL, 'subtypes' => NULL]) {
    $t_args = ['@types' => implode(', ', $types)];
    if (!$this->io()->confirm($this->t('Are you sure you want to create these types (@types)?', $t_args))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');

    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);

      // Get the default bundle for the schema type.
      // Default bundles are only defined for the 'media' and 'user'
      // entity types.
      $bundles = $mapping_type_storage->getDefaultSchemaTypeBundles($entity_type, $schema_type)
        ?: [$this->schemaNames->toDrupalName('types', $schema_type)];
      foreach ($bundles as $bundle) {
        // Create a new Schema.org mapping.
        $schemadotorg_mapping = SchemaDotOrgMapping::create([
          'target_entity_type_id' => $entity_type,
          'target_bundle' => $bundle,
          'type' => $schema_type,
        ]);

        /** @var \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm $form_object */
        $form_object = $this->entityTypeManager->getFormObject('schemadotorg_mapping', 'add');

        // Set the Schema.org mapping entity in the form object.
        $form_object->setEntity($schemadotorg_mapping);

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
  }

  /* ************************************************************************ */
  // Delete type.
  /* ************************************************************************ */

  /**
   * Validates the entity type and Schema.org type to be deleted.
   *
   * @hook validate schemadotorg:delete-type
   */
  public function deleteTypeValidate(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $types = $arguments['types'] ?? [];

    // Require Schema.org types.
    if (empty($types)) {
      throw new \Exception(dt('Schema.org types are required'));
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $schemadotorg_mapping_storage */
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    // Check for valid Schema.org mapping.
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);

      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $schemadotorg_mappings */
      $schemadotorg_mappings = $schemadotorg_mapping_storage->loadByProperties([
        'target_entity_type_id' => $entity_type,
        'type' => $schema_type,
      ]);

      if (empty($schemadotorg_mappings)) {
        $t_args = ['@entity_type' => $entity_type, '@schema_type' => $schema_type];
        throw new \Exception($this->t("No Schema.org mapping exists for @schema_type (@entity_type).", $t_args));
      }
    }
  }

  /**
   * Delete Schema.org type.
   *
   * @param array $types
   *   A list of Schema.org mapping types.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command schemadotorg:delete-type
   *
   * @usage drush schemadotorg:delete-type --delete-fields user:Person
   * @usage drush schemadotorg:delete-type --delete-fields media:AudioObject media:DataDownload media:ImageObject media:VideoObject
   * @usage drush schemadotorg:delete-type --delete-entity paragraph:ContactPoint paragraph:PostalAddress
   * @usage drush schemadotorg:delete-type --delete-entity node:Person node:Organization node:Place node:Event node:CreativeWork
   *
   * @option delete-entity Delete the entity associated with the Schema.org type.
   * @option delimiter Delete the fields associated with the Schema.org type.
   *
   * @aliases sode
   */
  public function deleteType(array $types, array $options = ['delete-entity' => FALSE, 'delete-fields' => FALSE]) {
    $t_args = ['@types' => implode(', ', $types)];
    if (!$this->io()->confirm($this->t('Are you sure you want to delete these Schema.org types (@types) and their associated entities and fields?', $t_args))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $schemadotorg_mapping_storage */
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    if ($options['delete-fields']) {
      /** @var \Drupal\field\FieldStorageConfigStorage $field_storage_config_storage */
      $field_storage_config_storage = $this->entityTypeManager->getStorage('field_storage_config');
      /** @var \Drupal\field\FieldConfigStorage $field_config_storage */
      $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    }

    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);

      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $schemadotorg_mappings */
      $schemadotorg_mappings = $schemadotorg_mapping_storage->loadByProperties([
        'target_entity_type_id' => $entity_type,
        'type' => $schema_type,
      ]);

      foreach ($schemadotorg_mappings as $schemadotorg_mapping) {
        $t_args = ['@schema_type' => $schema_type, '@id' => $schemadotorg_mapping->id()];

        $entity_type_id = $schemadotorg_mapping->getTargetEntityTypeId();
        $bundle = $schemadotorg_mapping->getTargetBundle();
        $target_entity_bundle = $schemadotorg_mapping->getTargetEntityBundleEntity();

        if ($options['delete-entity'] && $target_entity_bundle) {
          $target_entity_bundle->delete();
          $t_args = ['@label' => $target_entity_bundle->label()];
          $this->output()->writeln($this->t('The @label entity and its associated entities and fields has been deleted.', $t_args));
        }
        else {
          if ($options['delete-fields']) {
            $field_prefix = $this->schemaNames->getFieldPrefix();

            $deleted_fields = [];
            $properties = array_keys($schemadotorg_mapping->getSchemaProperties());
            foreach ($properties as $field_name) {
              if ($field_prefix && strpos($field_name, $field_prefix) === 0) {
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
            }
            $t_args['@fields'] = implode('; ', $deleted_fields);
            $this->output()->writeln($this->t('The associated Schema.org type @schema_type fields (@fields) have been deleted.', $t_args));
          }

          $schemadotorg_mapping->delete();
          $t_args = ['@schema_type' => $schema_type, '@id' => $schemadotorg_mapping->id()];
          $this->output()->writeln($this->t('Schema.org mapping @schema_type (@id) has been deleted.', $t_args));
          $this->output()->writeln('');
        }
      }
    }
  }

}
