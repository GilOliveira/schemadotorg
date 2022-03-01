<?php

namespace Drupal\schemadotorg\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org routes.
 */
class SchemaDotOrgReportsController extends ControllerBase {

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

  /**
   * Builds the Schema.org type or property item.
   *
   * @return array
   *   A renderable array containing a Schema.org type or property item.
   */
  public function index($id = '') {
    if ($id === '') {
      return $this->buildAbout();
    }
    elseif ($this->manager->isType($id)) {
      return $this->buildItem('types', $id);
    }
    elseif ($this->manager->isProperty($id)) {
      return $this->buildItem('properties', $id);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Builds the Schema.org types or properties documentation.
   *
   * @param string $table
   *   Schema.org types and properties table.
   *
   * @return array
   *   A renderable array containing Schema.org types or properties
   *   documentation.
   */
  public function docs(Request $request, $table) {
    $id = $request->query->get('id');

    switch ($table) {
      case 'types':
        return $this->buildTable('types', $id);

      case 'properties':
        return $this->buildTable('properties', $id);
    }

    throw new NotFoundHttpException();
  }

  /**
   * Builds the Schema.org types hierarchy.
   *
   * @return array
   *   A renderable array containing Schema.org types hierarchy.
   */
  public function hierarchy() {
    return $this->buildItemsRecursive(['Thing']);
  }

  /**
   * Builds the Schema.org data types.
   *
   * @return array
   *   A renderable array containing Schema.org data types.
   */
  public function dataTypes() {
    $data_types = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label'])
      ->condition('sub_type_of', '')
      ->condition('label', ['True', 'False', 'Thing'], 'NOT IN')
      ->orderBy('label')
      ->execute()
      ->fetchCol();
    return $this->buildItemsRecursive($data_types);
  }

  /**
   * Returns response for Schema.org  (types or properties) autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, $table) {
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse([]);
    }

    $query = $this->database->select('schemadotorg_' . $table, $table);
    $query->addField($table, 'label', 'value');
    $query->addField($table, 'label', 'label');
    $query->condition('label', '%' . $input . '%', 'LIKE');
    $query->orderBy('label');
    $query->range(0, 10);
    $labels = $query->execute()->fetchAllAssoc('label');
    return new JsonResponse(array_values($labels));
  }

  /* ************************************************************************ */
  // Build methods.
  /* ************************************************************************ */

  /**
   * Build Schema.org about page.
   *
   * @return array
   *   A renderable array containing Schema.org about page.
   */
  protected function buildAbout() {
    $build = [];

    // Introduction.
    $introduction = '<p>' . $this->t('Schema.org is a collaborative, community activity with a mission to create, maintain, and promote schemas for structured data on the Internet, on web pages, in email messages, and beyond.') . '<p>'
      . '<p>' . $this->t('Schema.org vocabulary can be used with many different encodings, including RDFa, Microdata and JSON-LD. These vocabularies cover entities, relationships between entities and actions, and can easily be extended through a well-documented extension model. Over 10 million sites use Schema.org to markup their web pages and email messages. Many applications from Google, Microsoft, Pinterest, Yandex and others already use these vocabularies to power rich, extensible experiences.') . '<p>'
      . '<p>' . $this->t('Founded by Google, Microsoft, Yahoo and Yandex, Schema.org vocabularies are developed by an open community process, using the public-schemaorg@w3.org mailing list and through GitHub.') . '<p>'
      . '<p>' . $this->t('A shared vocabulary makes it easier for webmasters and developers to decide on a schema and get the maximum benefit for their efforts. It is in this spirit that the founders, together with the larger community have come together - to provide a shared collection of schemas.') . '<p>';
    $build['introduction'] = ['#markup' => $introduction];

    // Divideo.
    $build['divider'] = ['#markup' => '<hr/>'];

    // Description.
    $build['description'] = ['#markup' => $this->t("The schemas are a set of 'types', each associated with a set of properties.")];

    // Types.
    $build['types'] = $this->getFilterForm('types');

    return $build;
  }

  /**
   * Build Schema.org type or property item.
   *
   * @param string $table
   *   Types or properties table name.
   * @param string $id
   *   Type or property id (a.k.a. label).
   *
   * @return array
   *   A renderable array containing Schema.org type or property item.
   */
  protected function buildItem($table, $id) {
    // Fields.
    $fields = ($table === 'types')
      ? $this->getTypeFields()
      : $this->getPropertyFields();

    // Item.
    $item = $this->getItem($table, $id);

    // Item.
    $t_args = [
      '@type' => ($table === 'types') ? $this->t('Type') : $this->t('Property'),
      '@id' => $id,
    ];
    $build = [];
    $build['#title'] = $this->t('Schema.org: @id (@type)', $t_args);
    foreach ($fields as $name => $label) {
      $value = $item[$name] ?? NULL;
      if (empty($value)) {
        continue;
      }

      $build[$name] = [
        '#type' => 'item',
        '#title' => $label,
      ];
      switch ($name) {
        case 'id':
          $build[$name]['link'] = [
            '#type' => 'link',
            '#title' => $value,
            '#url' => Url::fromUri($value),
          ];
          break;

        case 'label':
          $build[$name]['#plain_text'] = $value;
          break;

        case 'comment':
          $build[$name]['#markup'] = $this->formatComment($value);
          break;

        case 'sub_types':
          $types = $this->parseTypes($value);
          $build[$name]['links'] = $this->getLinks($value);
          $build[$name]['hierarchy'] = [
            '#type' => 'details',
            '#title' => $this->t('Sub types hierarchy'),
            'items' => $this->buildItemsRecursive($types),
          ];
          break;

        case 'sub_type_of':
          $build[$name]['links'] = $this->getLinks($value);
          $build[$name]['breadcrumbs'] = $this->buildTypeBreadcrumbs($id);
          break;

        default:
          $build[$name]['links'] = $this->getLinks($value);
      }
    }

    return $build;
  }

  /**
   * Build Schema.org types or properties table.
   *
   * @param string $table
   *   Types or properties table name.
   * @param string $id
   *   Type or property to filter by.
   *
   * @return array
   *   A render array containing Schema.org types or properties table.
   */
  protected function buildTable($table, $id) {
    // Header.
    $header = ($table === 'types')
      ? $this->getTypesHeader()
      : $this->getPropertiesHeader();

    // Query.
    $query = $this->database->select('schemadotorg_' . $table, $table);
    $query->fields($table, array_keys($header));
    $query->orderBy('label');
    if ($id) {
      $or = $query->orConditionGroup()
        ->condition('label', '%' . $id . '%', 'LIKE')
        ->condition('comment', '%' . $id . '%', 'LIKE');
      $query->condition($or);
    }
    $query = $query->extend(PagerSelectExtender::class)->limit(200);
    $result = $query->execute();

    // Rows.
    $rows = [];
    while ($record = $result->fetchAssoc()) {
      foreach ($record as $name => $value) {
        if ($name === 'comment') {
          $record[$name] = [
            'data' => ['#markup' => $this->formatComment($value)],
          ];
        }
        else {
          $links = $this->getLinks($value);
          if (count($links) > 20) {
            $record[$name] = [
              'data' => [
                '#type' => 'details',
                '#title' => $this->t('@count items', ['@count' => count($links)]),
                'content' => $links,
              ],
            ];
          }
          else {
            $record[$name] = ['data' => $links];
          }
        }
      }
      $rows[] = $record;
    }

    $t_args = [
      '@type' => ($table === 'types') ? $this->t('types') : $this->t('properties'),
    ];

    $build = [];
    $build['filter'] = $this->getFilterForm($table, $id);
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('No @type found.', $t_args),
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

  /**
   * Build Schema.org type as an item list recursively.
   *
   * @param array $ids
   *   An array of Schema.org type ids.
   *
   * @return array
   *   A renderable array containing Schema.org type as an item list.
   */
  protected function buildItemsRecursive(array $ids) {
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
      $items[$id] = [
        '#type' => 'link',
        '#title' => $id,
        '#url' => Url::fromRoute('schemadotorg.reports', ['id' => $id]),
      ];
      if ($type['sub_types']) {
        $sub_types = $this->parseTypes($type['sub_types']);
        $items[$id]['sub_types'] = $this->buildItemsRecursive($sub_types);
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /* ************************************************************************ */
  // Breadcrumb methods.
  /* ************************************************************************ */

  /**
   * Build Schema.org type breadcrumbs.
   *
   * @param string $type
   *   The current Schema.org type.
   *
   * @return array
   *   A renderable containing Schema.org type breadcrumbs.
   */
  protected function buildTypeBreadcrumbs($type) {
    $breadcrumbs = [];
    $breadcrumb_id = $type;
    $breadcrumbs[$breadcrumb_id] = [];
    $this->getTypeBreadcrumbsRecursive($breadcrumbs, $breadcrumb_id, $type);

    $build = [];
    foreach ($breadcrumbs as $links) {
      $links = array_reverse($links, TRUE);
      $breadcrumb_path = implode('/', array_keys($links));
      $build[$breadcrumb_path] = [
        '#theme' => 'breadcrumb',
        '#links' => $links,
      ];
    }
    ksort($build);
    return $build;
  }

  /**
   * Build type breadcrumbs recursivley.
   *
   * @param array &$breadcrumbs
   *   The type breadcrumbs.
   * @param string $breadcrumb_id
   *   The current breadcrumb id which is Schema.org type.
   * @param string $type
   *   The current Schema.org type.
   */
  protected function getTypeBreadcrumbsRecursive(array &$breadcrumbs, $breadcrumb_id, $type) {
    $item = $this->getItem('types', $type);
    $breadcrumbs[$breadcrumb_id][$type] = Link::createFromRoute($type, 'schemadotorg.reports', ['id' => $type]);

    $parent_types = $this->parseTypes($item['sub_type_of']);

    // Check if there are parents types.
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

  /* ************************************************************************ */
  // Types methods.
  /* ************************************************************************ */

  /**
   * Get Schema.org type fields.
   *
   * @return array
   *   Schema.org type fields.
   */
  protected function getTypeFields() {
    return [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'comment' => $this->t('Comment'),
      'sub_type_of' => $this->t('Sub type of'),
      'enumerationtype' => $this->t('Enumeration type'),
      'equivalent_class' => $this->t('Equivalent class'),
      'properties' => $this->t('Properties'),
      'sub_types' => $this->t('Sub types'),
      'supersedes' => $this->t('supersedes'),
      'superseded_by' => $this->t('Superseded by'),
      'is_part_of =>' => $this->t('Is part of'),
    ];
  }

  /**
   * Get Schema.org types table header.
   *
   * @return array[]
   *   Schema.org types table header.
   */
  public function getTypesHeader() {
    return [
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
        'data' => $this->t('Enumeration type'),
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
  }

  /* ************************************************************************ */
  // Properties.
  /* ************************************************************************ */

  /**
   * Get Schema.org property fields.
   *
   * @return array
   *   Schema.org Property fields.
   */
  protected function getPropertyFields() {
    return [
      'label' => $this->t('Label'),
      'comment' => $this->t('Comment'),
      'sub_property_of' => $this->t('Sub property of'),
      'equivalent_property' => $this->t('Equivalent property'),
      'subproperties' => $this->t('Subproperties'),
      'domain_includes' => $this->t('Domain includes'),
      'range_includes' => $this->t('Range includes'),
      'inverse_of' => $this->t('Inverse of'),
      'supersedes' => $this->t('Supersedes'),
      'superseded_by' => $this->t('Superseded by'),
      'is_part_of' => $this->t('Is part of'),
    ];
  }

  /**
   * Get properties table header.
   *
   * @return array[]
   *   Properties table header.
   */
  public function getPropertiesHeader() {
    return [
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
  }

  /* ************************************************************************ */
  // Helper methods.
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
    return $this->formBuilder->getForm('\Drupal\schemadotorg\Form\SchemaDotOrgReportsFilterForm', $table, $id);
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
        $url = Url::fromRoute('schemadotorg.reports', ['id' => $match[1]]);
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
    $types = $this->parseTypes($text);

    $links = [];
    foreach ($types as $type) {
      $prefix = ($links) ? ', ' : '';
      if (preg_match('#^[0-9A-Za-z]+$#', $type)) {
        $links[] = [
          '#type' => 'link',
          '#title' => $type,
          '#url' => Url::fromRoute('schemadotorg.reports', ['id' => $type]),
          '#prefix' => $prefix,
        ];
      }
      else {
        $links[] = ['#plain_text' => $type, '#prefix' => $prefix];
      }
    }
    return $links;
  }

  /**
   * Parse types from comma delimited list of Schema.org URLs.
   *
   * @param string $text
   *   A comma delimited list of Schema.org URLs.
   *
   * @return string[]
   *   An array of Schema.org types.
   */
  protected function parseTypes($text) {
    $text = trim($text);
    return $text
      ? explode(', ', str_replace('https://schema.org/', '', $text))
      : [];
  }

}
