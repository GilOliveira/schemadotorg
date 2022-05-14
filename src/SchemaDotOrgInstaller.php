<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Schema.org installer service.
 */
class SchemaDotOrgInstaller implements SchemaDotOrgInstallerInterface {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
      Connection $database,
      EntityTypeManagerInterface $entity_type_manager,
      SchemaDotOrgNamesInterface $schema_names,
      SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaNames = $schema_names;
    $this->schemaTypeManager = $schema_type_manager;
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
