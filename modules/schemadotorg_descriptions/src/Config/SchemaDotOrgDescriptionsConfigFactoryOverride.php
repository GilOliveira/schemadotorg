<?php

namespace Drupal\schemadotorg_descriptions\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideBase;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\StorageInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides Schema.org descriptions overrides for the configuration factory.
 *
 * @see \Drupal\config_override\SiteConfigOverrides
 * @see \Drupal\language\Config\LanguageConfigFactoryOverride
 * @see https://www.flocondetoile.fr/blog/dynamically-override-configuration-drupal-8
 * @see https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system
 */
class SchemaDotOrgDescriptionsConfigFactoryOverride extends ConfigFactoryOverrideBase implements ConfigFactoryOverrideInterface, EventSubscriberInterface {

  /**
   * The cache id.
   */
  const CACHE_ID = 'schemadotorg_descriptions.override';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * Constructs a SchemaDotOrgInstaller object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder
   *   The Schema.org schema type builder.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cacheBackend,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager,
    SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder
  ) {
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cacheBackend;
    $this->schemaTypeManager = $schema_type_manager;
    $this->schemaTypeBuilder = $schema_type_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = $this->getDescriptionOverrides();
    return array_intersect_key($overrides, array_flip($names));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'schemadotorg_descriptions';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * Reacts to the ConfigEvents::COLLECTION_INFO event.
   *
   * @param \Drupal\Core\Config\ConfigCollectionInfo $collection_info
   *   The configuration collection info event.
   */
  public function addCollections(ConfigCollectionInfo $collection_info) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $this->onConfigChange($event);
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $this->onConfigChange($event);
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $this->onConfigChange($event);
  }

  /**
   * Actions to be performed to configuration override on configuration rename.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config event.
   */
  public function onConfigChange(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();

    // Purge cached overrides when any mapping is updated.
    if (strpos($name, 'schemadotorg.schemadotorg_mapping.') === 0) {
      $this->cacheBackend->delete(static::CACHE_ID);
      return;
    }

    // Purge cached overrides when an entity or field definition is updated.
    $overrides = $this->getDescriptionOverrides();
    if (isset($overrides[$name])) {
      $this->cacheBackend->delete(static::CACHE_ID);
    }
  }

  /**
   * Get Schema.org description configuration overrides.
   *
   * @return array
   *   An array of description configuration overrides for
   *   mapped entity types and fields.
   */
  public function getDescriptionOverrides() {
    if ($config = $this->cacheBackend->get(static::CACHE_ID)) {
      return $config->data;
    }

    $type_overrides = [];
    $property_overrides = [];

    // Load the unaltered or not overridden Schema.org mapping configuration.
    $config_names = $this->configFactory->listAll('schemadotorg.schemadotorg_mapping.');
    foreach ($config_names as $config_name) {
      $config = $this->configFactory->getEditable($config_name);

      $type = $config->get('type');
      $entity_type_id = $config->get('target_entity_type_id');
      $bundle = $config->get('target_bundle');

      // Set entity type override.
      $type_overrides["$entity_type_id.type.$bundle"] = $type;

      // Set entity field instance override.
      $properties = $config->get('properties') ?: [];
      foreach ($properties as $field_name => $property_item) {
        $property = $property_item['property'];
        $property_overrides["field.field.$entity_type_id.$bundle.$field_name"] = $property;
      }
    }

    $this->setItemDescriptionOverrides('types', $type_overrides);
    $this->setItemDescriptionOverrides('properties', $property_overrides);
    $overrides = $type_overrides + $property_overrides;

    $this->cacheBackend->set(static::CACHE_ID, $overrides);

    return $overrides;
  }

  /**
   * Set configuration override descriptions for Schema.org types or properties.
   *
   * @param string $table
   *   Schema.org types or properties table.
   * @param array $overrides
   *   An associative array of configuration overrides.
   */
  protected function setItemDescriptionOverrides($table, array &$overrides) {
    $items = $this->schemaTypeManager->getItems($table, $overrides, ['label', 'comment']);
    $options = ['base_path' => 'https://schema.org/'];
    foreach ($overrides as $config_name => $id) {
      $data = $this->configFactory->getEditable($config_name)->getRawData();
      if (!isset($items[$id]) || empty($data) || !empty($data['description'])) {
        // Having empty overrides allows use to easily purge them as needed.
        // @see \Drupal\schemadotorg_descriptions\Config\SchemaDotOrgDescriptionsConfigFactoryOverride::onConfigChange
        $overrides[$config_name] = [];
      }
      else {
        $overrides[$config_name] = [
          'description' => $this->schemaTypeBuilder->formatComment($items[$id]['comment'], $options),
        ];
      }
    }
    return $overrides;
  }

}
