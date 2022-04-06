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
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
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
  use StringTranslationTrait;

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
   * Default cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $defaultCacheBackend;

  /**
   * Discovery cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $discoveryCacheBackend;

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $default_cache_backend
   *   The default cache backend.
   * @param \Drupal\Core\Cache\CacheBackendInterface $discovery_cache_backend
   *   The discovery cache backend.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder
   *   The Schema.org schema type builder.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $default_cache_backend,
    CacheBackendInterface $discovery_cache_backend,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager,
    SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder
  ) {
    $this->configFactory = $config_factory;
    $this->defaultCacheBackend = $default_cache_backend;
    $this->discoveryCacheBackend = $discovery_cache_backend;
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
      $this->resetDescriptionOverrides();
      return;
    }

    // Purge cached overrides when an entity or field definition is updated.
    $overrides = $this->getDescriptionOverrides();
    if (isset($overrides[$name])) {
      $this->resetDescriptionOverrides();
    }
  }

  /**
   * Reset Schema.org description configuration overrides.
   */
  public function resetDescriptionOverrides() {
    // Reset config.
    $this->configFactory->reset();
    // Reset default cache item.
    $this->defaultCacheBackend->delete(static::CACHE_ID);
    // Reset the entire plugin discovery cache.
    $this->discoveryCacheBackend->deleteAll();
  }

  /**
   * Get Schema.org description configuration overrides.
   *
   * @return array
   *   An array of description configuration overrides for
   *   mapped entity types and fields.
   */
  public function getDescriptionOverrides() {
    if ($cache = $this->defaultCacheBackend->get(static::CACHE_ID)) {
      return $cache->data;
    }

    $overrides = [];
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
      foreach ($properties as $field_name => $property) {
        $property_overrides["field.field.$entity_type_id.$bundle.$field_name"] = $property;
      }

      // Set subtype overrides.
      $this->setSubTypeDescriptionOverride($overrides, $entity_type_id, $bundle);
    }

    $this->setItemDescriptionOverrides('types', $type_overrides);
    $this->setItemDescriptionOverrides('properties', $property_overrides);
    $overrides += $type_overrides + $property_overrides;

    $this->defaultCacheBackend->set(static::CACHE_ID, $overrides);

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

  /**
   * Set subtype description override.
   *
   * @param array $overrides
   *   An array of override.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   */
  protected function setSubTypeDescriptionOverride(array &$overrides, $entity_type_id, $bundle) {
    $field_prefix = $this->configFactory
      ->getEditable('schemadotorg.settings')
      ->get('field_prefix');
    $subtype_field_name = $field_prefix . 'type';
    $config_name = "field.field.$entity_type_id.$bundle.$subtype_field_name";
    $data = $this->configFactory->getEditable($config_name)->getRawData();
    if ($data && empty($data['description'])) {
      $overrides["field.field.$entity_type_id.$bundle.$subtype_field_name"] = [
        'description' => $this->t('A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.'),
      ];
    }
    else {
      $overrides["field.field.$entity_type_id.$bundle.$subtype_field_name"] = [];
    }
  }

}
