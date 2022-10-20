<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_taxonomy;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org taxonomy vocabulary property manager.
 */
class SchemaDotOrgTaxonomyDefaultVocabularyManager implements SchemaDotOrgTaxonomyDefaultVocabularyManagerInterface {
  use StringTranslationTrait;
  use SchemaDotOrgTaxonomyTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a SchemaDotOrgTaxonomyDefaultVocabularyManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface|null $content_translation_manager
   *   The content translation manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    ?ContentTranslationManagerInterface $content_translation_manager = NULL
  ) {
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $display_repository;
    $this->contentTranslationManager = $content_translation_manager;
  }

  /**
   * Add default vocabulary to content types when a mapping is inserted.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    $entity_type = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    $group_name = 'group_taxonomy';

    // Make sure we are adding default vocabularies to nodes.
    if ($entity_type !== 'node') {
      return;
    }

    $group_label = $this->configFactory->get('schemadotorg_taxonomy.settings')
      ->get('default_vocabularies_group_label');
    $default_vocabularies = $this->configFactory->get('schemadotorg_taxonomy.settings')
      ->get('default_vocabularies');
    foreach ($default_vocabularies as $vocabulary_id => $vocabulary_settings) {
      $field_name = 'field_' . $vocabulary_id;

      // Create vocabulary.
      $vocabulary = $this->createVocabulary($vocabulary_id, $vocabulary_settings);

      // Create the field storage.
      $field_storage = FieldStorageConfig::loadByName('node', $field_name);
      if (!FieldStorageConfig::loadByName('node', $field_name)) {
        $field_storage = FieldStorageConfig::create([
          'field_name' => $field_name,
          'entity_type' => $entity_type,
          'type' => 'entity_reference',
          'settings' => ['target_type' => 'taxonomy_term'],
          'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
        ]);
        $field_storage->save();
      }

      // Create the field instance.
      $field_config = FieldConfig::loadByName('node', $bundle, $field_name);
      if (!$field_config) {
        FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => $bundle,
          'label' => $vocabulary->label(),
          'settings' => [
            'handler' => 'default:taxonomy_term',
            'handler_settings' => [
              'target_bundles' => [$vocabulary_id => $vocabulary_id],
              'auto_create' => $vocabulary_settings['auto_create'] ?? FALSE,
            ],
          ],
        ])->save();
      }

      // Create the form display component.
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type, $bundle);
      if ($this->moduleHandler->moduleExists('entity_reference_tree')) {
        $form_display->setComponent($field_name, [
          'type' => 'entity_reference_tree',
          'settings' => [
            'theme' => 'default',
            'dots' => 0,
            'size' => 60,
            'placeholder' => '',
            'match_operator' => 'CONTAINS',
            'match_limit' => 10,
            'dialog_title' => (string) $this->t('Select items'),
            'label' => (string) $this->t('Select items'),
          ],
        ]);
      }
      else {
        $form_display->setComponent($field_name, [
          'type' => 'entity_reference_autocomplete_tags',
        ]);
      }
      if ($this->moduleHandler->moduleExists('field_group')) {
        $group = $form_display->getThirdPartySetting('field_group', $group_name);
        if (!$group) {
          $group = [
            'label' => $group_label,
            'children' => [],
            'parent_name' => '',
            // Same weight as meta tag sidebar.
            'weight' => 120,
            'format_type' => 'details',
            'format_settings' => ['open' => TRUE],
            'region' => 'content',
          ];
        }
        $group['children'][] = $field_name;
        $group['children'] = array_unique($group['children']);
        $form_display->setThirdPartySetting('field_group', $group_name, $group);
      }
      $form_display->save();

      // Create the view display component.
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type, $bundle);
      $view_display->setComponent($field_name, [
        'type' => 'entity_reference_label',
      ]);
      if ($this->moduleHandler->moduleExists('field_group')) {
        $group = $view_display->getThirdPartySetting('field_group', $group_name);
        if (!$group) {
          $group = [
            'label' => $group_label,
            'children' => [$field_name],
            'parent_name' => '',
            // Before links.
            'weight' => 99,
            'format_type' => 'fieldset',
            'format_settings' => [],
            'region' => 'content',
          ];
        }
        $group['children'][] = $field_name;
        $group['children'] = array_unique($group['children']);
        $view_display->setThirdPartySetting('field_group', $group_name, $group);
      }
      $view_display->save();
    }
  }

}
