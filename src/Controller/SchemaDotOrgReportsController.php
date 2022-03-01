<?php

namespace Drupal\schemadotorg\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Form\FormBuilderInterface;
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
    $build['types'] = $this->formBuilder->getForm('\Drupal\schemadotorg\Form\SchemaDotOrgReportsTypesFilterForm');

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

    // Query.
    $record = $this->database->select('schemadotorg_' . $table, $table)
      ->fields($table, array_keys($fields))
      ->condition('label', $id)
      ->execute()
      ->fetchAssoc();

    // Item.
    $t_args = [
      '@type' => ($table === 'types') ? $this->t('Type') : $this->t('Property'),
      '@id' => $id,
    ];
    $build = [];
    $build['#title'] = $this->t('Schema.org: @id (@type)', $t_args);
    foreach ($fields as $name => $label) {
      if (empty($record[$name])) {
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
            '#title' => $record[$name],
            '#url' => Url::fromUri($record[$name]),
          ];
          break;

        case 'label':
          $build[$name]['#plain_text'] = $record[$name];
          break;

        case 'comment':
          $build[$name]['#markup'] = $this->formatComment($record[$name]);
          break;

        default:
          $build[$name]['links'] = $this->getLinks($record[$name]);
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
    $build['filter'] = $this->formBuilder->getForm('\Drupal\schemadotorg\Form\SchemaDotOrgReports' . ucfirst($table) . 'FilterForm', $id);
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
    return $links;
  }

}
