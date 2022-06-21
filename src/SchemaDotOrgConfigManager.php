<?php

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
   * SchemaDotOrgConfig constructor.
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
  public function repair() {
    $config = $this->configFactory->getEditable('schemadotorg.settings');

    // Default properties sorted by path/breadcrumb.
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

    // Sorting.
    $sort = [
      'ksort' => [
        'schema_types.main_properties',
        'schema_properties.range_includes',
        'schema_properties.default_fields',
        'names.custom_words',
        'names.custom_names',
        'names.prefixes',
        'names.suffixes',
        'names.abbreviations',
      ],
      'sort' => [
        'schema_properties.ignored_properties',
        'names.acronyms',
        'names.minor_words',
      ],
    ];
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
