<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;

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
  public function parseItems($text) {
    $text = trim($text);
    if (empty($text)) {
      return [];
    }

    $items = explode(', ', str_replace('https://schema.org/', '', $text));
    return array_combine($items, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($table, $id, array $fields = []) {
    $table_name = 'schemadotorg_' . $table;
    if (empty($fields)) {
      return $this->database->query('SELECT *
        FROM {' . $this->database->escapeTable($table_name) . '}
        WHERE label=:id', [':id' => $id])->fetchAssoc();
    }
    else {
      return $this->database->select($table_name, 't')
        ->fields('t', $fields)
        ->condition('label', $id)
        ->execute()
        ->fetchAssoc();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getType($type, array $fields = []) {
    return $this->getItem('types', $type, $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty($property, array $fields = []) {
    return $this->getItem('properties', $property, $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeChildren($type) {
    $type_definition = $this->getType($type, ['sub_types']);

    // Subtypes.
    $children = $this->parseItems($type_definition['sub_types']);

    // Enumerations.
    $enumeration_types = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label'])
      ->condition('enumerationtype', 'https://schema.org/' . $type)
      ->orderBy('label')
      ->execute()
      ->fetchCol();
    if ($enumeration_types) {
      $children += array_combine($enumeration_types, $enumeration_types);
    }

    return $children;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllTypeChildren($type, array $fields = [], array $ignored_types = []) {
    return $this->getTypesChildrenRecursive([$type], $fields, $ignored_types);
  }

  /**
   * Get all Schema.org types below a specified array of types.
   *
   * @param array $types
   *   An array of Schema.org type ids.
   * @param array $fields
   *   An array of Schema.org type fields.
   * @param array $ignored_types
   *   An array of ignored Schema.org type ids.
   *
   * @return array
   *   An associative array of Schema.org types keyed by type.
   *
   * @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportControllerBase::buildItemsRecursive
   */
  protected function getTypesChildrenRecursive(array $types, array $fields = [], array $ignored_types = []) {
    $fields = $fields ?: ['label', 'sub_types', 'sub_type_of'];

    $items = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', $fields)
      ->condition('label', $types, 'IN')
      ->orderBy('label')
      ->execute()
      ->fetchAllAssoc('label', \PDO::FETCH_ASSOC);
    foreach ($items as $id => $item) {
      // Get children.
      $children = $this->getTypeChildren($id);

      // Remove ignored types from children.
      if ($ignored_types) {
        $children = array_diff_key($children, $ignored_types);
      }

      if ($children) {
        $items += $this->getTypesChildrenRecursive($children, $fields, $ignored_types);
      }
    }
    return $items;
  }

}
