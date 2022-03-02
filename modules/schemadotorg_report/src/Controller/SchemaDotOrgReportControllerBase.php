<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org report routes.
 */
abstract class SchemaDotOrgReportControllerBase extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The Schema.org manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgManagerInterface
   */
  protected $manager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\schemadotorg\SchemaDotOrgManagerInterface $schemedotorg_manager
   *   The Schema.org manager service.
   */
  public function __construct(Connection $database, FormBuilderInterface $form_builder, SchemaDotOrgManagerInterface $schemedotorg_manager) {
    $this->database = $database;
    $this->formBuilder = $form_builder;
    $this->manager = $schemedotorg_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('form_builder'),
      $container->get('schemadotorg.manager')
    );
  }

  /* ************************************************************************ */
  // Build methods.
  /* ************************************************************************ */

  /**
   * Get Schema.org types or properties filter form.
   *
   * @param string $table
   *   Types or properties table name.
   * @param string $id
   *   Type or property to filter by.
   *
   * @return array
   *   The form array.
   */
  protected function getFilterForm($table, $id = '') {
    return $this->formBuilder->getForm('\Drupal\schemadotorg_report\Form\SchemaDotOrgReportFilterForm', $table, $id);
  }

  /**
   * Build table info.
   *
   * @param string $table
   *   Types or properties table name.
   * @param int $count
   *   The item count to display.
   *
   * @return array
   *   A renderable array containing item count info.
   */
  protected function buildTableInfo($table, $count) {
    if ($table === 'warnings') {
      $info = $this->formatPlural($count, '@count warning', '@count warnings');
    }
    elseif ($table === 'types') {
      $info = $this->formatPlural($count, '@count type', '@count types');
    }
    elseif ($table === 'properties') {
      $info = $this->formatPlural($count, '@count property', '@count properties');
    }
    else {
      $info = $this->formatPlural($count, '@count item', '@count items');
    }
    return [
      '#markup' => $info,
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Build a table cell.
   *
   * @param string $name
   *   Table cell name.
   * @param string $value
   *   Table cell value.
   *
   * @return array[]|string
   *   A renderable array containing a table cell.
   */
  public function buildTableCell($name, $value) {
    switch ($name) {
      case 'drupal_name':
      case 'drupal_label':
        return $value;

      case 'comment':
        return ['data' => ['#markup' => $this->formatComment($value)]];

      default:
        $links = $this->getLinks($value);
        if (count($links) > 20) {
          return [
            'data' => [
              '#type' => 'details',
              '#title' => $this->t('@count items', ['@count' => count($links)]),
              'content' => $links,
            ],
          ];
        }
        else {
          return ['data' => $links];
        }
    }
  }

  /**
   * Build Schema.org type as an item list recursively.
   *
   * @param array $ids
   *   An array of Schema.org type ids.
   * @param array $ignored_types
   *   An array of ignored Schema.org type ids.
   *
   * @return array
   *   A renderable array containing Schema.org type as an item list.
   */
  protected function buildItemsRecursive(array $ids, array $ignored_types = []) {
    if (empty($ids)) {
      return [];
    }

    $types = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label', 'sub_types'])
      ->condition('label', $ids, 'IN')
      ->orderBy('label')
      ->execute()
      ->fetchAllAssoc('label', \PDO::FETCH_ASSOC);

    $items = [];
    foreach ($types as $id => $type) {
      // Item.
      $items[$id] = [
        '#type' => 'link',
        '#title' => $id,
        '#url' => $this->getItemUrl($id),
      ];

      // Subtypes.
      $sub_types = $this->manager->parseItems($type['sub_types']);
      if ($ignored_types) {
        $sub_types = array_diff($sub_types, $ignored_types);
      }
      if ($sub_types) {
        $items[$id]['sub_types'] = $this->buildItemsRecursive($sub_types, $ignored_types);
      }

      // Enumerations.
      $enumeration_types = $this->database->select('schemadotorg_types', 'types')
        ->fields('types', ['label'])
        ->condition('enumerationtype', 'https://schema.org/' . $id)
        ->orderBy('label')
        ->execute()
        ->fetchCol();
      if ($enumeration_types) {
        $items[$id]['enumeration'] = $this->buildItemsRecursive($enumeration_types);
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /* ************************************************************************ */
  // API methods.
  /* ************************************************************************ */

  /**
   * Get Schema.org type or property URL.
   *
   * @param string $id
   *   Type or property ID.
   *
   * @return \Drupal\Core\Url
   *   Schema.org type or property URL.
   */
  protected function getItemUrl($id) {
    return Url::fromRoute('schemadotorg_reports', ['id' => $id]);
  }

  /**
   * Get Schema.org type or property item.
   *
   * @param string $table
   *   Types or properties table name.
   * @param string $id
   *   Type or property ID.
   *
   * @return array
   *   A Schema.org type or property item.
   */
  protected function getItem($table, $id) {
    $fields = ($table === 'types')
      ? $this->getTypeFields()
      : $this->getPropertyFields();

    return $this->database->select('schemadotorg_' . $table, $table)
      ->fields($table, array_keys($fields))
      ->condition('label', $id)
      ->execute()
      ->fetchAssoc();
  }

  /* ************************************************************************ */
  // Helper methods.
  /* ************************************************************************ */

  /**
   * Format Schema.org type or property comment.
   *
   * @param string $comment
   *   A comment.
   *
   * @return string
   *   Formatted Schema.org type or property comment with links to details.
   */
  protected function formatComment($comment) {
    if (strpos($comment, 'href="/') === FALSE) {
      return $comment;
    }
    $dom = Html::load($comment);
    $a_nodes = $dom->getElementsByTagName('a');
    foreach ($a_nodes as $a_node) {
      $href = $a_node->getAttribute('href');
      if (preg_match('#^/([0-9A-Za-z]+)$#', $href, $match)) {
        $url = $this->getItemUrl($match[1]);
        $a_node->setAttribute('href', $url->toString());
      }
    }
    return Html::serialize($dom);
  }

  /**
   * Get links for Schema.org items (types or properties).
   *
   * @param string $text
   *   A string of comma delimited items (types or properties).
   *
   * @return array
   *   An array of links for Schema.org items (types or properties).
   */
  protected function getLinks($text) {
    $types = $this->manager->parseItems($text);

    $links = [];
    foreach ($types as $type) {
      $prefix = ($links) ? ', ' : '';
      if (preg_match('#^[0-9A-Za-z]+$#', $type)) {
        $links[] = [
          '#type' => 'link',
          '#title' => $type,
          '#url' => $this->getItemUrl($type),
          '#prefix' => $prefix,
        ];
      }
      else {
        $links[] = ['#plain_text' => $type, '#prefix' => $prefix];
      }
    }
    return $links;
  }

}
