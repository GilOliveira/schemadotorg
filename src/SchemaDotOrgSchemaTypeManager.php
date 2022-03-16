<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;

/**
 * Schema.org schema type manager.
 */
class SchemaDotOrgSchemaTypeManager implements SchemaDotOrgSchemaTypeManagerInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Schema.org items cache.
   *
   * @var array
   */
  protected $itemsCache = [];

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
   * Get Schema.org type or property URI.
   *
   * @param string $id
   *   A Schema.org type or property.
   *
   * @return string
   *   Schema.org type or property URI.
   */
  public function getUri($id) {
    return static::URI . $id;
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
  public function isItem($id) {
    return $this->isType($id) || $this->isProperty($id);
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
  public function isDataType($id) {
    $data_types = $this->getDataTypes();
    return (isset($data_types[$id]));
  }

  /**
   * {@inheritdoc}
   */
  public function isEnumerationType($id) {
    return (boolean) $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['id'])
      ->condition('enumerationtype', $this->getUri($id))
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnumerationValue($id) {
    $item = $this->getItem('types', $id);
    return (!empty($item['enumerationtype']));
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
  public function parseIds($text) {
    $text = trim($text);
    if (empty($text)) {
      return [];
    }

    $items = explode(', ', str_replace(static::URI, '', $text));
    return array_combine($items, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($table, $id, array $fields = []) {
    $table_name = 'schemadotorg_' . $table;
    if (empty($fields)) {
      if (!isset($this->itemsCache[$table][$id])) {
        $this->itemsCache[$table][$id] = $this->database->query('SELECT *
          FROM {' . $this->database->escapeTable($table_name) . '}
          WHERE label=:id', [':id' => $id])->fetchAssoc();
      }
      return $this->itemsCache[$table][$id];
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
  public function getTypeProperties($type, array $fields = []) {
    $type_definition = $this->getType($type);
    $properties = $this->parseIds($type_definition['properties']);
    return $this->database->select('schemadotorg_properties', 'properties')
      ->fields('properties', $fields)
      ->condition('label', $properties, 'IN')
      ->orderBy('label')
      ->execute()
      ->fetchAllAssoc('label', \PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeChildren($type) {
    $type_definition = $this->getType($type, ['sub_types']);

    // Subtypes.
    $children = $this->parseIds($type_definition['sub_types']);

    // Enumerations.
    $enumeration_types = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label'])
      ->condition('enumerationtype', $this->getUri($type))
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
  public function getSubtypes($type) {
    $type_definition = $this->getType($type, ['sub_types']);
    return $this->parseIds($type_definition['sub_types']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEnumerations($type) {
    $enumeration_types = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label'])
      ->condition('enumerationtype', $this->getUri($type))
      ->orderBy('label')
      ->execute()
      ->fetchCol();
    return ($enumeration_types) ? array_combine($enumeration_types, $enumeration_types) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTypes() {
    $labels = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label'])
      ->condition('sub_type_of', '')
      ->condition('label', 'Thing', '<>')
      ->orderBy('label')
      ->execute()
      ->fetchCol();
    $data_types = array_combine($labels, $labels);
    foreach ($data_types as $data_type) {
      $data_types += $this->getTypeChildren($data_type);
    }
    return $data_types;
  }

  /**
   * Build Schema.org type hierarchical tree.
   *
   * @param string|array $type
   *   A Schema.org type or an array of types.
   * @param array $ignored_types
   *   An array of ignored Schema.org types.
   *
   * @return array
   *   A renderable array containing Schema.org type hierarchical tree.
   */
  public function getTypeTree($type, array $ignored_types = []) {
    if ($ignored_types) {
      $ignored_types = array_combine($ignored_types, $ignored_types);
    }
    return $this->getTypeTreeRecursive((array) $type, $ignored_types);
  }

  /**
   * Build Schema.org type hierarchical tree recursively.
   *
   * @param array $types
   *   An array of Schema.org type.
   * @param array $ignored_types
   *   An array of ignored Schema.org types.
   *
   * @return array
   *   A renderable array containing Schema.org type hierarchical tree.
   */
  protected function getTypeTreeRecursive(array $types, array $ignored_types = []) {
    if (empty($types)) {
      return [];
    }

    // We must make sure the types are not deprecated or does not exist.
    // @see https://schema.org/docs/attic.home.html
    $types = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label'])
      ->condition('label', $types, 'IN')
      ->orderBy('label')
      ->execute()
      ->fetchCol();

    // Remove ignored types.
    if ($types) {
      $types = array_diff_key($types, $ignored_types);
    }

    $tree = [];
    foreach ($types as $type) {
      $subtypes = $this->getSubtypes($type);
      $enumerations = $this->getEnumerations($type);
      $tree[$type] = [
        'subtypes' => $this->getTypeTreeRecursive($subtypes, $ignored_types),
        'enumerations' => $this->getTypeTreeRecursive($enumerations, $ignored_types),
      ];
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllSubTypes(array $types) {
    if (!isset($this->tree)) {
      $this->tree = [];
      $result = $this->database->select('schemadotorg_types', 'types')
        ->fields('types', ['label', 'sub_types'])
        ->orderBy('label')
        ->execute();
      while ($record = $result->fetchAssoc()) {
        $this->tree[$record['label']] = $this->parseIds($record['sub_types']);
      }
    }

    $all_subtypes = [];

    $types = array_combine($types, $types);
    while ($types) {
      $all_subtypes += $types;
      $subtypes = [];
      foreach ($types as $type) {
        $subtypes += array_combine($this->tree[$type], $this->tree[$type]);
      }
      $types = $subtypes;
    }
    return $all_subtypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllTypeChildren($type, array $fields = [], array $ignored_types = []) {
    if ($ignored_types) {
      $ignored_types = array_combine($ignored_types, $ignored_types);
    }
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

  /**
   * Build Schema.org type breadcrumbs.
   *
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   An array containing Schema.org type breadcrumbs.
   */
  public function getTypeBreadcrumbs($type) {
    $breadcrumbs = [];
    $breadcrumb_id = $type;
    $breadcrumbs[$breadcrumb_id] = [];
    $this->getTypeBreadcrumbsRecursive($breadcrumbs, $breadcrumb_id, $type);

    $sorted_breadcrumbs = [];
    foreach ($breadcrumbs as $breadcrumb) {
      $sorted_breadcrumb = array_reverse($breadcrumb, TRUE);
      $breadcrumb_path = implode('/', array_keys($sorted_breadcrumb));
      $sorted_breadcrumbs[$breadcrumb_path] = $sorted_breadcrumb;
    }
    ksort($sorted_breadcrumbs);
    return $sorted_breadcrumbs;
  }

  /**
   * Build type breadcrumbs recursively.
   *
   * @param array &$breadcrumbs
   *   The type breadcrumbs.
   * @param string $breadcrumb_id
   *   The breadcrumb id which is a Schema.org type.
   * @param string $type
   *   The Schema.org type.
   */
  protected function getTypeBreadcrumbsRecursive(array &$breadcrumbs, $breadcrumb_id, $type) {
    $breadcrumbs[$breadcrumb_id][$type] = $type;

    $item = $this->getItem('types', $type, ['sub_type_of']);
    $parent_types = $this->parseIds($item['sub_type_of']);
    if (empty($parent_types)) {
      return;
    }

    // Store a reference to the current breadcrumb.
    $current_breadcrumb = $breadcrumbs[$breadcrumb_id];

    // The first parent type is appended to the current breadcrumb.
    $parent_type = array_shift($parent_types);
    $this->getTypeBreadcrumbsRecursive($breadcrumbs, $breadcrumb_id, $parent_type);

    // All additional parent types needs to start a new breadcrumb.
    foreach ($parent_types as $parent_type) {
      $breadcrumbs[$parent_type] = $current_breadcrumb;
      $this->getTypeBreadcrumbsRecursive($breadcrumbs, $parent_type, $parent_type);
    }
  }

}