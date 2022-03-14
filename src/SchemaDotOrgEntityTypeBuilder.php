<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\node\Entity\NodeType;

/**
 * Schema.org entity type builder service.
 */
class SchemaDotOrgEntityTypeBuilder implements SchemaDotOrgEntityTypeBuilderInterface {

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
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->entityDisplayRepository = $display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaNames = $schema_names;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createTypeVocabulary($type) {
    $type_definition = $this->schemaTypeManager->getType($type);

    // Create vocabulary.
    $vocabulary_id = 'schema_' . $type_definition['drupal_name'];
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

  /**
   * Add a field to an entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $field
   *   The field to be added to the entity.
   */
  public function addFieldToEntity($entity_type_id, $bundle, array $field) {
    $field_name = $field['machine_name'];
    $field_label = $field['label'];

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config_storage */
    $field_storage_config_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $field_storage_config = $field_storage_config_storage->load($entity_type_id . '.' . $field_name);
    if (!$field_storage_config) {
      $field_cardinality = $field['unlimited'] ? -1 : 1;

      // @see \Drupal\field_ui\Form\FieldStorageAddForm::submitForm
      if (strpos($field['type'], 'field_ui:') !== FALSE) {
        [, $field_type, $option_key] = explode(':', $field['type'], 3);

        /** @var FieldTypePluginManagerInterface $field_type_plugin_manager */
        $field_type_plugin_manager = \Drupal::service('plugin.manager.field.field_type');
        $field_definition = $field_type_plugin_manager->getDefinition($field_type);
        $options = $field_type_plugin_manager->getPreconfiguredOptions($field_definition['id']);
        $field_options = $options[$option_key];
      }
      else {
        $field_type = $field['type'];
        $field_options = [];
      }
      $field_options += ['field_storage_config' => []];

      /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
      $field_storage_config_storage->create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'type' => $field_type,
        'cardinality' => $field_cardinality,
      ] + $field_options['field_storage_config'])->save();
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $field_config_storage */
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $field_config_storage->load($entity_type_id . '.' . $bundle . '.' . $field_name);
    if (!$field_config) {
      $field_description = $field['description'] ?? '';
      $field_config_storage->create([
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
        'field_name' => $field_name,
        'label' => $field_label,
        'description' => $field_description,
      ])->save();
    }

    $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle);
    if (!$form_display->getComponent($field_name)) {
      $form_display->setComponent($field_name)->save();
    }

    $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle);
    if (!$view_display->getComponent($field_name)) {
      $view_display->setComponent($field_name)->save();
    }
  }

}
