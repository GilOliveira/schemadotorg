<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Schema.org config manager service.
 */
class SchemaDotOrgConfigManager implements SchemaDotOrgConfigManagerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgConfigManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->configFactory = $config_factory;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchemaTypeDefaultProperties(string $schema_type, array|string|null $add = NULL, array|string|null $remove = NULL): void {
    $config = $this->configFactory->getEditable('schemadotorg.settings');

    // Get or create default properties.
    $default_properties = $config->get("schema_types.default_properties.$schema_type") ?? [];

    // Remove default properties.
    if ($remove) {
      $remove = (array) $remove;
      $default_properties = array_filter($default_properties, function ($property) use ($remove) {
        return !in_array($property, $remove);
      });
    }

    // Add default properties.
    if ($add) {
      $add = (array) $add;
      $default_properties = array_merge($default_properties, $add);
      $default_properties = array_unique($default_properties);
    }

    // Sort default properties.
    sort($default_properties);

    // Save default properties.
    $config->set("schema_types.default_properties.$schema_type", $default_properties)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function repair(): void {
    // Default properties sorted by path/breadcrumb.
    $config = $this->configFactory->getEditable('schemadotorg.settings');
    $default_properties = $config->get('schema_types.default_properties');
    $paths = [];
    foreach (array_keys($default_properties) as $type) {
      $breadcrumbs = $this->schemaTypeManager->getTypeBreadcrumbs($type);
      $path = array_key_first($breadcrumbs);
      $paths[$path] = $type;
    }
    ksort($paths);
    $sorted_default_properties = [];
    foreach ($paths as $type) {
      $properties = $default_properties[$type];
      sort($properties);
      $sorted_default_properties[$type] = $properties;
    }
    $config->set('schema_types.default_properties', $sorted_default_properties);
    $config->save();

    // Config sorting.
    $config_sort = [
      'schemadotorg.settings' => [
        'ksort' => [
          'schema_types.main_properties',
          'schema_properties.range_includes',
          'schema_properties.default_fields',
        ],
        'sort' => [
          'schema_properties.ignored_properties',
        ],
      ],
      'schemadotorg.names' => [
        'ksort' => [
          'custom_words',
          'custom_names',
          'prefixes',
          'suffixes',
          'abbreviations',
        ],
        'sort' => [
          'acronyms',
          'minor_words',
        ],
      ],
    ];
    foreach ($config_sort as $config_name => $sort) {
      $config = $this->configFactory->getEditable($config_name);
      foreach ($sort as $method => $keys) {
        foreach ($keys as $key) {
          $value = $config->get($key);
          if (!$value) {
            throw new \Exception('Unable to locate ' . $key);
          }
          $method($value);
          $config->set($key, $value);
        }
      }
      $config->save();
    }
  }

}
