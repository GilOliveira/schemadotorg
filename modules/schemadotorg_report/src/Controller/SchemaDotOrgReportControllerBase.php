<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
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
   * The Schema.org schema data type manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaDataTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->formBuilder = $container->get('form_builder');
    $instance->schemaDataTypeManager = $container->get('schemadotorg.schema_type_manager');
    return $instance;
  }

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
   * Build info.
   *
   * @param string $type
   *   Type of info being displayed.
   * @param int $count
   *   The item count to display.
   *
   * @return array
   *   A renderable array containing info.
   */
  protected function buildInfo($type, $count) {
    switch ($type) {
      case 'Thing':
        $info = $this->formatPlural($count, '@count thing', '@count things');
        break;

      case 'Intangible':
        $info = $this->formatPlural($count, '@count intangible', '@count intangibles');
        break;

      case 'Enumeration':
        $info = $this->formatPlural($count, '@count enumeration', '@count enumerations');
        break;

      case 'StructuredValue':
        $info = $this->formatPlural($count, '@count structured value', '@count structured values');
        break;

      case 'DataTypes':
        $info = $this->formatPlural($count, '@count data type', '@count data types');
        break;

      case 'types':
        $info = $this->formatPlural($count, '@count type', '@count types');
        break;

      case 'properties':
        $info = $this->formatPlural($count, '@count property', '@count properties');
        break;

      default:
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
  protected function buildTableCell($name, $value) {
    switch ($name) {
      case 'drupal_name':
      case 'drupal_label':
        return $value;

      case 'comment':
        return ['data' => ['#markup' => $this->formatComment($value)]];

      default:
        $links = $this->buildItemsLinks($value);
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
   * Build Schema.org type tree as an item list recursively.
   *
   * @param array $tree
   *   An array of Schema.org type tree.
   *
   * @return array
   *   A renderable array containing Schema.org type tree as an item list.
   *
   * @see \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManager::getTypesChildrenRecursive
   */
  protected function buildTypeTreeRecursive(array $tree) {
    if (empty($tree)) {
      return [];
    }

    $items = [];
    foreach ($tree as $type => $item) {
      $items[$type] = [
        '#type' => 'link',
        '#title' => $type,
        '#url' => $this->getItemUrl($type),
      ];
      $children = $item['subtypes'] + $item['enumerations'];
      $items[$type]['children'] = $this->buildTypeTreeRecursive($children);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * Build links to Schema.org items (types or properties).
   *
   * @param string $text
   *   A string of comma delimited items (types or properties).
   *
   * @return array
   *   An array of links to Schema.org items (types or properties).
   */
  protected function buildItemsLinks($text) {
    $ids = $this->schemaDataTypeManager->parseIds($text);

    $links = [];
    foreach ($ids as $id) {
      $prefix = ($links) ? ', ' : '';
      if ($this->schemaDataTypeManager->isItem($id)) {
        $links[] = [
          '#type' => 'link',
          '#title' => $id,
          '#url' => $this->getItemUrl($id),
          '#prefix' => $prefix,
        ];
      }
      else {
        $links[] = ['#plain_text' => $id, '#prefix' => $prefix];
      }
    }
    return $links;
  }

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

}
