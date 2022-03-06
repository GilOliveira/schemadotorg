<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 * Schema.org builder service.
 */
class SchemaDotOrgBuilder implements SchemaDotOrgBuilderInterface {

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
  protected $schemaDotOrgNames;

  /**
   * The Schema.org manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgManagerInterface
   */
  protected $schemaDotOrgManager;

  /**
   * Constructs a SchemaDotOrgBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgManagerInterface $schemedotorg_manager
   *   The Schema.org manager service.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemedotorg_names
   *   The Schema.org names service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    SchemaDotOrgManagerInterface $schemedotorg_manager,
    SchemaDotOrgNamesInterface $schemedotorg_names
  ) {
    $this->entityDisplayRepository = $display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaDotOrgManager = $schemedotorg_manager;
    $this->schemaDotOrgNames = $schemedotorg_names;
  }

  /**
   * {@inheritdoc}
   */
  public function createTypeVocabulary($type) {
    $type_definition = $this->schemaDotOrgManager->getType($type);

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

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
    $field_storage_config = $this->entityTypeManager->getStorage('field_storage_config');
    if (!$field_storage_config->load($entity_type_id . '.' . $field_name)) {
      $field_storage_config->create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'type' => 'string',
        'settings' => ['max_length' => 255],
      ])->save();
    }

    /** @var \Drupal\field\FieldConfigInterface $field_storage_config */
    $field_config = $this->entityTypeManager->getStorage('field_config');
    if (!$field_config->load($entity_type_id . '.' . $bundle . '.' . $field_name)) {
      $field_config->create([
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

}
