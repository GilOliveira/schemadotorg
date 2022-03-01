<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;
use Drupal\schemadotorg\Utilty\SchemaDotOrgStringHelper;

/**
 * Schema.org manager service.
 */
class SchemaDotOrgManager implements SchemaDotOrgManagerInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a SchemaDotOrgInstaller object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function isId($table, $id) {
    return (boolean) $this->database->select('schemadotorg_' . $table, 't')
      ->fields('t', ['id'])
      ->condition('label', $id)
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function isType($id) {
    return $this->isId('types', $id);
  }

  /**
   * {@inheritdoc}
   */
  public function isProperty($id) {
    return $this->isId('properties', $id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($name) {
    $filename = __DIR__ . '/../data/schemaorg-current-https-' . $name . '.csv';
    $handle = fopen($filename, 'r');

    // Get field names.
    $schema_names = fgetcsv($handle);
    $field_definitions = [];
    foreach ($schema_names as $schema_name) {
      $column_name = SchemaDotOrgStringHelper::camelCaseToSnakeCase($schema_name);
      $field_definitions[$column_name] = [
        'label' => SchemaDotOrgStringHelper::toLabel($schema_name),
        'column_name' => $column_name,
        'schema_name' => $schema_name,
      ];
    }
    return $field_definitions;
  }

}
