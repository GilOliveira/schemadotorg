<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\schemadotorg\Utilty\SchemaDotOrgStringHelper;
use Drupal\taxonomy\Entity\Term;

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
   * The Schema.org manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgManagerInterface
   */
  protected $schemaDotOrgManager;

  /**
   * Constructs a SchemaDotOrgInstaller object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgManagerInterface $schemedotorg_manager
   *   The Schema.org manager service.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, SchemaDotOrgManagerInterface $schemedotorg_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaDotOrgManager = $schemedotorg_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function install() {
    $this->reinstallSchema();
    $this->importTable('properties');
    $this->importTable('types');
    $this->updateTypesVocabulary();
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
      $field_name = SchemaDotOrgStringHelper::camelCaseToSnakeCase($field_name);
    });

    // Insert records.
    while ($row = fgetcsv($handle)) {
      $fields = [];
      foreach ($field_names as $index => $field_name) {
        $fields[$field_name] = $row[$index] ?? '';
      }
      $fields['drupal_label'] = SchemaDotOrgStringHelper::toLabel($fields['label']);
      $fields['drupal_name'] = SchemaDotOrgStringHelper::toDrupalName($fields['label']);
      $this->database->insert($table)
        ->fields($fields)
        ->execute();
    }
  }

  /**
   * Reinstall Scheme.org tables.
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

  /**
   * Update the Schema.org types vocabulary (schemadotorg_types).
   */
  protected function updateTypesVocabulary() {
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Create terms lookup table.
    /** @var \Drupal\taxonomy\TermInterface[] $terms_lookup */
    $terms_lookup = [];
    $terms = $term_storage->loadByProperties(['vid' => 'schemadotorg_types']);
    foreach ($terms as $term) {
      $terms_lookup[$term->field_schemadotorg_type->value] = $term;
    }

    // Get types below 'Thing'.
    // This prevents data types from being added to the vocabulary.
    $types = $this->schemaDotOrgManager->getTypeChildren('Thing');

    // First pass: Insert new Schema.org types.
    foreach ($types as $type => $item) {
      if (!isset($terms_lookup[$type])) {
        $term = $term_storage->create([
          'name' => SchemaDotOrgStringHelper::toLabel($type),
          'vid' => 'schemadotorg_types',
          'field_schemadotorg_type' => ['value' => $type],
        ]);
        $term->save();
        $terms_lookup[$type] = $term;
      }
    }

    // Second path: Build Schema.org type hierarchy.
    foreach ($types as $type => $item) {
      // Get parent values.
      $value = [];
      $parent_types = $this->schemaDotOrgManager->parseItems($item['sub_type_of']);
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
