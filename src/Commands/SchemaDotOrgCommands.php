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
   * @param SchemaDotOrgInstallerInterface $schemadotorg_installer
   *   The Schema.org installer service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgInstallerInterface $schemadotorg_installer,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    parent::__construct();
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaInstaller = $schemadotorg_installer;
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
    $schema_types = $arguments['schema_types'] ?? [];
    $entity_type = $arguments['entity_type'] ?? NULL;

    // Required Schema.org type.
    if (empty($schema_types)) {
      throw new \Exception(dt('Schema.org types are required'));
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');

    // Check for allowed and valid entity type.
    $entity_types = $mapping_type_storage->getEntityTypes();
    if (!in_array($entity_type, $entity_types)) {
      $t_args = [
        '@entity_type' => $entity_type,
        '@entity_types' => implode(', ', $entity_types),
      ];
      throw new \Exception($this->t("The entity type '@entity_type' is not valid. Please select a entity type (@entity_types).", $t_args));
    }

    // Check for valid Schema.org types.
    foreach ($schema_types as $schema_type) {
      if (!$this->schemaTypeManager->isType($schema_type)) {
        $t_args = ['@type' => $schema_type];
        throw new \Exception($this->t("The Schema.org type '@type' is not valid. Please go to https://schema.org to find valid Schema.org types.", $t_args));
      }
    }
  }

  /**
   * Create Schema.org type.
   *
   * @param string $entity_type
   *   An entity type.
   * @param array $schema_types
   *   A list of Schema.org types.
   *
   * @command schemadotorg:create-type
   *
   * @usage drush schemadotorg:create-type user Person
   * @usage drush schemadotorg:create-type media AudioObject DataDownload ImageObject VideoObject
   * @usage drush schemadotorg:create-type paragraph ContactPoint PostalAddress
   * @usage drush schemadotorg:create-type node Person Organization Place Event CreativeWork
   *
   * @aliases socr
   */
  public function createType($entity_type, array $schema_types) {
    $t_args = ['@schema_types' => implode(', ', $schema_types)];
    if (!$this->io()->confirm($this->t('Are you sure you want to create Schema.org types (@schema_types)?', $t_args))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');

    foreach ($schema_types as $schema_type) {
      // Get the default bundle for the schema type.
      // Default bundles are only defined for the 'media' and 'user'
      // entity types.
      $bundle = $mapping_type_storage->getDefaultSchemaTypeBundle($entity_type, $schema_type);

      // Create a new Schema.org mapping.
      $schemadotorg_mapping = SchemaDotOrgMapping::create([
        'target_entity_type_id' => $entity_type,
        'target_bundle' => $bundle,
        'type' => $schema_type,
      ]);

      /** @var \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm $form_object */
      $form_object = $this->entityTypeManager->getFormObject('schemadotorg_mapping', 'add');
      $form_object->setEntity($schemadotorg_mapping);

      // Submit the form.
      $form_state = new FormState();
      $this->formBuilder->submitForm($form_object, $form_state);
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
    $entity_type = $arguments['entity_type'] ?? NULL;
    $schema_types = $arguments['schema_types'] ?? [];

    // Required Schema.org type.
    if (empty($schema_types)) {
      throw new \Exception(dt('Schema.org types are required'));
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $schemadotorg_mapping_storage */
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    // Check for valid Schema.org mapping.
    foreach ($schema_types as $schema_type) {
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
   * @param string $entity_type
   *   An entity type.
   * @param array $schema_types
   *   A list of Schema.org types.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command schemadotorg:delete-type
   *
   * @usage drush schemadotorg:delete-type --delete-fields user Person
   * @usage drush schemadotorg:delete-type --delete-fields media AudioObject DataDownload ImageObject VideoObject
   * @usage drush schemadotorg:delete-type --delete-entity paragraph ContactPoint PostalAddress
   * @usage drush schemadotorg:delete-type --delete-entity node Person Organization Place Event CreativeWork
   *
   * @option delete-entity Delete the entity associated with the Schema.org type.
   * @option delimiter Delete the fields associated with the Schema.org type.
   *
   * @aliases sode
   */
  public function deleteType($entity_type, array $schema_types, $options = ['delete-entity' => FALSE, 'delete-fields' => FALSE]) {
    $t_args = ['@schema_types' => implode(', ', $schema_types)];
    if (!$this->io()->confirm($this->t('Are you sure you want to delete these Schema.org types (@schema_types) and their associated entities and fields?', $t_args))) {
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

    foreach ($schema_types as $schema_type) {
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
            $deleted_fields = [];
            $properties = array_keys($schemadotorg_mapping->getSchemaProperties());
            foreach ($properties as $field_name) {
              if (strpos($field_name, 'schema_') === 0) {
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
