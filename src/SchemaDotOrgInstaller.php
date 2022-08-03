<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Schema.org installer service.
 */
class SchemaDotOrgInstaller implements SchemaDotOrgInstallerInterface {
  use StringTranslationTrait;

  /**
   * Schema.org version.
   */
  const VERSION = '14.0';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * Constructs a SchemaDotOrgInstaller object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    Connection $database,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaNames = $schema_names;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function requirements($phase) {
    if ($phase !== 'runtime') {
      return [];
    }

    // NOTE: Suggestions are also included the Schema.org Blueprints
    // composer.json file.
    $recommended_modules = [
      'datetime' => [
        'title' => $this->t('Datetime'),
        'description' => $this->t('Defines datetime form elements and a datetime field type.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/datetime',
      ],
      'link' => [
        'title' => $this->t('Link'),
        'description' => $this->t('Provides a simple link field type.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/link',
      ],
      'media' => [
        'title' => $this->t('Media'),
        'description' => $this->t('Manages the creation, configuration, and display of media items.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/media',
      ],
      'media_library' => [
        'title' => $this->t('Media Library'),
        'description' => $this->t('Enhances the media list with additional features to more easily find and use existing media items.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/media_library',
      ],
      'telephone' => [
        'title' => $this->t('Telephone'),
        'description' => $this->t('Defines a field type for telephone numbers.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/telephone',
      ],
      'field_group' => [
        'title' => $this->t('Field Group'),
        'description' => $this->t('Provides the ability to group your fields on both form and display.'),
        'uri' => 'https://www.drupal.org/project/field_group',
      ],
      'address' => [
        'title' => $this->t('Address'),
        'description' => $this->t('Provides functionality for storing, validating and displaying international postal addresses.'),
        'uri' => 'https://www.drupal.org/project/address',
      ],
    ];

    $installed_modules = $this->moduleHandler->getModuleList();
    $missing_modules = array_diff_key($recommended_modules, $installed_modules);
    if (empty($missing_modules)) {
      return [];
    }

    $module_names = [];
    $module_items = [];
    foreach ($missing_modules as $missing_module) {
      $module_names[] = $missing_module['title'];
      $module_items[] = [
        'title' => [
          '#type' => 'link',
          '#title' => $missing_module['title'],
          '#url' => Url::fromUri($missing_module['uri']),
          '#suffix' => '</br>',
        ],
        'description' => [
          '#markup' => $missing_module['description'],
        ],
      ];
    }

    $requirements = [];

    $requirements['schemadotorg_modules'] = [
      'title' => $this->t('Schema.org Blueprints: Recommended modules missing'),
      'value' => $this->t('Recommended modules missing: %module_list.', ['%module_list' => implode(', ', $module_names)]),
      'description' => [
        'content' => [
          '#markup' => $this->t('The below recommend help intergrate and support Schema.org mappings, entities, and fields.'),
        ],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $module_items,
        ],
      ],
      'severity' => REQUIREMENT_WARNING,
    ];

    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public function install() {
    // Recreate Schema.org types and properties tables.
    // Recreating these readonly tables allows us to continually refine and
    // optimize the table schemas.
    $this->reinstallSchema();

    // Import Schema.org types and properties tables.
    $this->importTable('types');
    $this->importTable('properties');
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    $schema = [];

    // Schema.org: Types.
    // @see https://raw.githubusercontent.com/schemaorg/schemaorg/main/data/releases/13.0/schemaorg-current-https-types.csv
    $schema['schemadotorg_types'] = [
      'description' => 'Schema.org types',
      'fields' => [
        'id' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
        ],
        'label' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'comment' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'sub_type_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'enumerationtype' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'equivalent_class' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'properties' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'sub_types' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'supersedes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'superseded_by' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'is_part_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'label' => ['label'],
      ],
    ];
    // Schema.org: Properties.
    // @see https://raw.githubusercontent.com/schemaorg/schemaorg/main/data/releases/13.0/schemaorg-current-https-properties.csv
    $schema['schemadotorg_properties'] = [
      'description' => 'Schema.org properties',
      'fields' => [
        'id' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
        ],
        'label' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'comment' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'sub_property_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'equivalent_property' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'subproperties' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'domain_includes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'range_includes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'inverse_of' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'supersedes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'superseded_by' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'is_part_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'label' => ['label'],
      ],
    ];

    return $schema;
  }

  /**
   * Import Schema.org types and properties tables.
   */
  public function importTables() {
    $this->importTable('types');
    $this->importTable('properties');
  }

  /**
   * Installs and populates Schema.org table.
   *
   * @param string $name
   *   The Schema.org table type (properties or types).
   */
  protected function importTable($name) {
    $table = 'schemadotorg_' . $name;
    $filename = __DIR__ . '/../data/' . static::VERSION . '/schemaorg-current-https-' . $name . '.csv';

    // Truncate table.
    $this->database->truncate($table)->execute();

    // Load CSV.
    $handle = fopen($filename, 'r');

    // Get field names.
    $fields = fgetcsv($handle);
    array_walk($fields, function (&$field_name) {
      $field_name = $this->schemaNames->camelCaseToSnakeCase($field_name);
    });

    // Insert multiple records.
    $query = $this->database->insert($table)->fields($fields);
    while ($row = fgetcsv($handle)) {
      $values = [];
      foreach ($fields as $index => $field_name) {
        $values[$field_name] = $row[$index] ?? '';
      }
      $query->values($values);
    }
    $query->execute();
  }

  /**
   * Reinstall Schema.org tables.
   */
  protected function reinstallSchema() {
    $tables = $this->schema();
    foreach ($tables as $name => $table) {
      if ($this->database->schema()->tableExists($name)) {
        $this->database->schema()->dropTable($name);
      }
      $this->database->schema()->createTable($name, $table);
    }
  }

}
