<?php

namespace Drupal\schemadotorg_translation;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org translate manager.
 */
class SchemaDotOrgTranslationManager implements SchemaDotOrgTranslationManagerInterface {

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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a SchemaDotOrgTranslationManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $field_manager,
    ContentTranslationManagerInterface $content_translation_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->contentTranslationManager = $content_translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enableMapping(SchemaDotOrgMappingInterface $mapping) {
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    if (!$this->isEntityTranslated($entity_type_id, $bundle)) {
      return;
    }
    $this->enableEntityType($entity_type_id, $bundle);
    // $this->enableEntityFields($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function enableField($field) {
    $entity_type_id = $field->getTargetEntityTypeId();
    $bundle = $field->getTargetBundle();
    $field_name = $field->getName();
    if (!$this->isFieldTranslated($entity_type_id, $bundle, $field_name)) {
      $field->setTranslatable(FALSE)->save();
      return;
    }

    // Set translatable.
    $field->setTranslatable(TRUE);

    // Set third party settings.
    $field_type = $field->getType();
    switch ($field_type) {
      case 'image':
        $column_settings = [
          'alt' => 'alt',
          'title' => 'title',
          'file' => 0,
        ];
        $field->setThirdPartySetting('content_translation', 'translation_sync', $column_settings);
        break;
    }

    // Save config.
    $field->save();
  }

  /**
   * Enable translation for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   */
  protected function enableEntityType($entity_type_id, $bundle) {
    if (!$this->isEntityTranslated($entity_type_id, $bundle)) {
      return;
    }

    // Enable translations for entity type.
    $config = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    $config->save();

    // Store whether a bundle has translation enabled or not.
    $this->contentTranslationManager->setEnabled($entity_type_id, $bundle, TRUE);
  }

  /**
   * Enable translation for an entity field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   */
  protected function enableEntityFields($entity_type_id, $bundle) {
    $fields = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field) {
      $this->enableField($field->getConfig($bundle));
    }
  }

  /**
   * Load Schema.org mapping.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   A Schema.org mapping.
   */
  protected function loadMapping($entity_type_id, $bundle) {
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $mapping_storage->load("$entity_type_id.$bundle");
  }

  /**
   * Determine if an entity should be translated.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return bool
   *   TRUE if an entity should be translated.
   */
  protected function isEntityTranslated($entity_type_id, $bundle) {
    // Check that Schema.org mapping exists.
    $mapping = $this->loadMapping($entity_type_id, $bundle);
    if (empty($mapping)) {
      return FALSE;
    }

    // Check that Schema.org mapping exists.
    $mapping = $this->loadMapping($entity_type_id, $bundle);
    if (empty($mapping)) {
      return FALSE;
    }

    $config = $this->configFactory->get('schemadotorg_translation.settings');

    // Check excluded Schema.org types.
    $schema_type = $mapping->getSchemaType();
    if (in_array($schema_type, $config->get('excluded_schema_properties'))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Determine if a field should be translated.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE if a field should be translated.
   */
  protected function isFieldTranslated($entity_type_id, $bundle, $field_name) {
    // Check that Schema.org mapping exists.
    if (!$this->isEntityTranslated($entity_type_id, $bundle)) {
      return FALSE;
    }

    $config = $this->configFactory->get('schemadotorg_translation.settings');

    // Check excluded Schema.org properties.
    $mapping = $this->loadMapping($entity_type_id, $bundle);
    $schema_type = $mapping->getSchemaType();
    $schema_properties = $mapping->getSchemaProperties();
    $schema_property = $schema_properties[$field_name] ?? '';
    $excluded_schema_properties = $config->get('excluded_schema_properties');
    if (in_array($schema_property, $excluded_schema_properties)
      || in_array("$schema_type--$schema_property", $excluded_schema_properties)) {
      return FALSE;
    }

    $fields = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $field = $fields[$field_name];
    $field_type = $field->getType();

    // Check excluded field names.
    if (in_array($field_name, $config->get('excluded_field_names'))) {
      return FALSE;
    }

    // Check included field names.
    if (in_array($field_name, $config->get('included_field_names'))) {
      return TRUE;
    }

    // Check included field types.
    if (in_array($field_type, $config->get('included_field_types'))) {
      return TRUE;
    }

    return FALSE;
  }

}
