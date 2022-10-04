<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_taxonomy;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org taxonomy vocabulary property manager.
 */
class SchemaDotOrgTaxonomyPropertyVocabularyManager implements SchemaDotOrgTaxonomyPropertyVocabularyManagerInterface {
  use StringTranslationTrait;

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
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a SchemaDotOrgTaxonomyPropertyVocabularyManager object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface|null $content_translation_manager
   *   The content translation manager.
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager,
    ?ContentTranslationManagerInterface $content_translation_manager = NULL
  ) {
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->contentTranslationManager = $content_translation_manager;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function propertyFieldTypeAlter(array &$field_types, string $schema_type, string $schema_property): void {
    $property_vocabulary_settings = $this->getPropertyVocabularySettings($schema_property);
    if ($property_vocabulary_settings) {
      $field_types = ['field_ui:entity_reference:taxonomy_term' => 'field_ui:entity_reference:taxonomy_term'] + $field_types;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function propertyFieldAlter(
    string $schema_type,
    string $schema_property,
    array &$field_storage_values,
    array &$field_values,
    ?string &$widget_id,
    array &$widget_settings,
    ?string &$formatter_id,
    array &$formatter_settings
  ): void {
    // Make sure the field type is set to 'entity_reference' with the target type
    // set to 'taxonomy_term'.
    $is_entity_reference_taxonomy_term = ($field_storage_values['type'] === 'entity_reference' && $field_storage_values['settings']['target_type'] === 'taxonomy_term');
    if (!$is_entity_reference_taxonomy_term) {
      return;
    }

    // Check to see if the Schema.org property has vocabulary settings.
    $property_vocabulary_settings = $this->getPropertyVocabularySettings($schema_property);
    if (!$property_vocabulary_settings) {
      return;
    }

    // Set default vocabulary id and label from field name and field label.
    $property_definition = $this->schemaTypeManager->getProperty($schema_property);
    $property_vocabulary_settings += [
      'id ' => $field_storage_values['field_name'],
      'label' => $field_values['label'],
      'description' => $property_definition['comment'],
    ];

    $vid = $property_vocabulary_settings['id'];

    // Make sure the vocabulary exists, if not create it.
    /** @var \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage */
    $vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabulary = $vocabulary_storage->load($vid);
    if (!$vocabulary) {
      // Create the vocabulary.
      $vocabulary = $vocabulary_storage->create([
        'vid' => $vid,
        'name' => $property_vocabulary_settings['label'],
        'description' => $property_vocabulary_settings['description'],
      ]);
      $vocabulary->save();

      // Enable translations for the vocabulary's taxonomy terms.
      if ($this->contentTranslationManager) {
        $this->contentTranslationManager->setEnabled('taxonomy_term', $vid, TRUE);
      }

      $edit_link = $vocabulary->toLink($this->t('Edit'), 'edit-form')->toString();
      $this->messenger->addStatus($this->t('Created new vocabulary %name.', ['%name' => $vocabulary->label()]));
      $this->logger->get('taxonomy')->notice('Created new vocabulary %name.', ['%name' => $vocabulary->label(), 'link' => $edit_link]);
    }

    // Set the term reference's default handler, target bundle, and allow
    // the creation of terms if they don't already exist.
    // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::setDefaultFieldValues
    $field_values['settings'] = [
      'handler' => 'default:taxonomy_term',
      'handler_settings' => [
        'target_bundles' => [$vocabulary->id() => $vocabulary->id()],
        'auto_create' => TRUE,
      ],
    ];

    // Use the tags widget.
    $widget_id = 'entity_reference_autocomplete_tags';
  }

  /**
   * Get a taxonomy Schema.org default property.
   *
   * @param string $property
   *   The Schema.org property.
   *
   * @return array|null
   *   A Schema.org default property vocabulary definition.
   */
  protected function getPropertyVocabularySettings(string $property): ?array {
    return $this->configFactory->get('schemadotorg_taxonomy.settings')
      ->get("property_vocabularies.$property");
  }

}
