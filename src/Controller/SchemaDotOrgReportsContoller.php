<?php

namespace Drupal\schemadotorg\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org routes.
 */
class SchemaDotOrgReportsContoller extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @param \Drupal\schemadotorg\SchemaDotOrgManagerInterface $schemedotorg_manager
   *   The Schema.org manager service.
   */
  public function __construct(Connection $database, SchemaDotOrgManagerInterface $schemedotorg_manager) {
    $this->database = $database;
    $this->manager = $schemedotorg_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('schemadotorg.manager')
    );
  }

  /**
   * Builds the Schema.org type or property details.
   *
   * @return array
   *   A renderable array containing a Schema.org type or property detailsw.
   */
  public function index($id) {
    if ($this->manager->isType($id)) {
      return $this->type($id);
    }
    elseif ($this->manager->isProperty($id)) {
      return $this->property($id);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  protected function type($id) {
    $build = [];
    $build['#title'] = $this->t('Schema.org: Type: @id', ['@id' => $id]);
    return $build;
  }

  protected function property($id) {
    $build = [];
    $build['#title'] = $this->t('Schema.org: Property: @id', ['@id' => $id]);
    return $build;
  }

  /**
   * Builds the Schema.org type report.
   */
  public function types() {
    // Header.
    $header = [
      'label' => [
        'data' => $this->t('Label'),
      ],
      'comment' => [
        'data' => $this->t('Comment'),
      ],
      'sub_type_of' => [
        'data' => $this->t('Sub type of'),
      ],
      'enumerationtype' => [
        'data' => $this->t('Enumerationtype'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'equivalent_class' => [
        'data' => $this->t('Equivalent class'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'properties' => [
        'data' => $this->t('Properties'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'sub_types' => [
        'data' => $this->t('Sub types'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'supersedes' => [
        'data' => $this->t('Supersedes'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'superseded_by' => [
        'data' => $this->t('Superseded by'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $this->buildTable('types', $header);
  }

  /**
   * Builds the Schema.org properties report.
   */
  public function properties() {
    // Header.
    $header = [
      'label' => [
        'data' => $this->t('Label'),
      ],
      'comment' => [
        'data' => $this->t('Comment'),
      ],
      'sub_property_of' => [
        'data' => $this->t('Sub property of'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'equivalent_property' => [
        'data' => $this->t('Equivalent property'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'subproperties' => [
        'data' => $this->t('Subproperties'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'domain_includes' => [
        'data' => $this->t('Domain includes'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'range_includes' => [
        'data' => $this->t('Range includes'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'inverse_of' => [
        'data' => $this->t('Inverse of'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'supersedes' => [
        'data' => $this->t('Supersedes'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'superseded_by' => [
        'data' => $this->t('Superseded by'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'is_part_of' => [
        'data' => $this->t('Is part of'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $this->buildTable('properties', $header);
  }

  /* ************************************************************************ */
  // Helper methods.
  /* ************************************************************************ */

  /**
   * Build Schema.org types or properties table.
   *
   * @param string $table
   *   Type of table.
   * @param array $header
   *   Table header.
   *
   * @return array
   *   A render array containing Schema.org types or properties table.
   */
  protected function buildTable($table, array $header) {
    $query = $this->database->select('schemadotorg_' . $table, 't')
      ->fields('t', array_keys($header))
      ->orderBy('label');
    $query = $query->extend(PagerSelectExtender::class)->limit(200);
    $result = $query->execute();

    // Rows.
    $rows = [];
    while ($record = $result->fetchAssoc()) {
      foreach ($record as $name => $value) {
        $record[$name] = ($name === 'comment')
          ? $this->buildComment($value)
          : $this->buildItems($value);
      }
      $rows[] = $record;
    }
    return [
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#sticky' => TRUE,
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

  /**
   * Build Schema.org type or property comment.
   *
   * @param string $comment
   *   A comment.
   *
   * @return array
   *   A render array containing Schema.org type or property comment.
   */
  protected function buildComment($comment) {
    if (strpos($comment, 'href="/') !== FALSE) {
      $dom = Html::load($comment);
      $a_nodes = $dom->getElementsByTagName('a');
      foreach ($a_nodes as $a_node) {
        $href = $a_node->getAttribute('href');
        if (preg_match('#^/([0-9A-Za-z]+)$#', $href, $match)) {
          $url = Url::fromRoute('schemadotorg.reports', ['id' => $match[1]]);
          $a_node->setAttribute('href', $url->toString());
        }
      }
      $comment = Html::serialize($dom);
    }

    return [
      'data' => ['#markup' => $comment],
    ];
  }

  /**
   * Build Schema.org items (types or properties).
   *
   * @param string $text
   *   A string of comma delimited items (types or properties).
   *
   * @return array
   *   A render array containing linked Schema.org items (types or properties).
   */
  protected function buildItems($text) {
    if (empty($text)) {
      return [];
    }

    $links = [];
    $items = explode(', ', $text);
    foreach ($items as $item) {
      $prefix = ($links) ? ', ' : '';
      $id = str_replace('https://schema.org/', '', $item);
      if (preg_match('#^[0-9A-Za-z]+$#', $id)) {
        $links[] = [
          '#type' => 'link',
          '#title' => $id,
          '#url' => Url::fromRoute('schemadotorg.reports', ['id' => $id]),
          '#prefix' => $prefix,
        ];
      }
      else {
        $links[] = ['#plain_text' => $item, '#prefix' => $prefix];
      }
    }
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
