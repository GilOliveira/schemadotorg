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
  const VERSION = '13.0';

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
   * The Schema.org schema data type manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaDataTypeManager;

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * The Schema.org builder service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgBuilderInterface
   */
  protected $schemaBuilder;

  /**
   * Schema.org type vocabularies.
   *
   * @var string[]
   */
  protected $typeVocabularies = [
    'Thing',
    'Intangible',
    'Enumeration',
    'StructuredValue',
  ];

  /**
   * Constructs a SchemaDotOrgInstaller object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_data_type_manager
   *   The Schema.org schema data type manager service.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgBuilderInterface $schema_builder
   *   The Schema.org builder service.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_data_type_manager,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgBuilderInterface $schema_builder
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaDataTypeManager = $schema_data_type_manager;
    $this->schemaNames = $schema_names;
    $this->schemaBuilder = $schema_builder;
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

    // Create and update Schema.org type vocabularies.
    $this->createTypeVocabularies();
    $this->updateTypeVocabularies();
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
        'drupal_name' => [
          'type' => 'varchar_ascii',
          // @todo Lower to 32 characters.
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'drupal_label' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'label' => ['label'],
        'drupal_name' => ['drupal_name'],
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
        'drupal_name' => [
          'type' => 'varchar_ascii',
          // @todo Lower to 32 characters.
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'drupal_label' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'label' => ['label'],
        'drupal_name' => ['drupal_name'],
      ],
    ];

    return $schema;
  }

  /**
   * Installs and populatas Schema.org table.
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
    $field_names = fgetcsv($handle);
    array_walk($field_names, function (&$field_name) {
      $field_name = $this->schemaNames->camelCaseToSnakeCase($field_name);
    });

    // Insert records.
    while ($row = fgetcsv($handle)) {
      $fields = [];
      foreach ($field_names as $index => $field_name) {
        $fields[$field_name] = $row[$index] ?? '';
      }
      $fields['drupal_label'] = $this->schemaNames->camelCaseToTitleCase($fields['label']);
      $fields['drupal_name'] = $this->schemaNames->toDrupalName($fields['label']);
      $this->database->insert($table)
        ->fields($fields)
        ->execute();
    }
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

  /* ************************************************************************ */
  // Type vocabularies.
  /* ************************************************************************ */

  /**
   * Create type vocabularies.
   */
  protected function createTypeVocabularies() {
    foreach ($this->typeVocabularies as $type_vocabulary) {
      $this->schemaBuilder->createTypeVocabulary($type_vocabulary);
    }
  }

  /**
   * Update the Schema.org type vocabularies.
   */
  protected function updateTypeVocabularies() {
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    foreach ($this->typeVocabularies as $type_vocabulary) {
      $type_definition = $this->schemaDataTypeManager->getType($type_vocabulary);

      $entity_id = 'schema_' . $type_definition['drupal_name'];

      // Create terms lookup table.
      /** @var \Drupal\taxonomy\TermInterface[] $terms_lookup */
      $terms_lookup = [];
      $terms = $term_storage->loadByProperties(['vid' => $entity_id]);
      foreach ($terms as $term) {
        $terms_lookup[$term->schema_type->value] = $term;
      }

      $types = $this->schemaDataTypeManager->getAllTypeChildren(
        $type_vocabulary,
        ['label', 'drupal_label', 'sub_type_of'],
        $this->typeVocabularies
      );

      // First pass: Insert new Schema.org types.
      foreach ($types as $type => $item) {
        if (!isset($terms_lookup[$type])) {
          $term = $term_storage->create([
            'name' => $item['drupal_label'],
            'vid' => $entity_id,
            'schema_type' => ['value' => $type],
          ]);
          $term->save();
          $terms_lookup[$type] = $term;
        }
      }

      // Second pass: Build Schema.org type hierarchy.
      foreach ($types as $type => $item) {
        // Get parent values.
        $value = [];
        $parent_types = $this->schemaDataTypeManager->parseIds($item['sub_type_of']);
        foreach ($parent_types as $parent_type) {
          if (isset($terms_lookup[$parent_type])) {
            $parent_term = $terms_lookup[$parent_type];
            $value[] = ['target_id' => $parent_term->id()];
          }
        }

        // Re-save the term.
        $term = $terms_lookup[$type];
        $term->parent->setValue($value);
        $term->save();
      }
    }
  }

}
