<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org report routes.
 */
class SchemaDotOrgReportItemController extends SchemaDotOrgReportControllerBase {

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

    // Divider.
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
        case 'drupal_label':
        case 'drupal_name':
          $build[$name]['#plain_text'] = $value;
          break;

        case 'comment':
          $build[$name]['#markup'] = $this->formatComment($value);
          break;

        case 'properties':
          $properties = $this->manager->parseItems($value);
          $build[$name] = [
            '#type' => 'details',
            '#title' => $label,
            '#open' => TRUE,
            'items' => $this->buildTypeProperties($properties),
          ];
          break;

        default:
          $build[$name]['links'] = $this->getLinks($value);
      }
    }

    // Custom fields.
    if ($table === 'types') {
      // Parents.
      $build['parents'] = [
        '#type' => 'details',
        '#title' => $this->t('Parent types'),
        '#open' => TRUE,
        'breadcrumbs' => $this->buildTypeBreadcrumbs($id),
      ];

      // Subtype.
      if ($item['sub_types']) {
        $subtypes = $this->manager->parseItems($item['sub_types']);
        $build['sub_types_hierarchy'] = [
          '#type' => 'details',
          '#title' => $this->t('More specific types'),
          'items' => $this->buildItemsRecursive($subtypes),
        ];
      }

      // Enumerations.
      $build['enumerations'] = $this->buildTypeEnumerations($id);
    }

    return $build;
  }

  /**
   * Build Schema.org type properties table.
   *
   * @param array $properties
   *   An array of Schema.org properties.
   *
   * @return array
   *   A renderable array containing a Schema.org type properties table.
   */
  protected function buildTypeProperties(array $properties) {
    $header = [
      'label' => [
        'data' => $this->t('Schema.org label'),
      ],
      'drupal_label' => [
        'data' => $this->t('Drupal label'),
      ],
      'drupal_name' => [
        'data' => $this->t('Drupal name'),
      ],
      'comment' => [
        'data' => $this->t('Comment'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'range_includes' => [
        'data' => $this->t('Range includes'),
      ],
    ];

    // Query.
    $result = $this->database->select('schemadotorg_properties', 'properties')
      ->fields('properties', array_keys($header))
      ->condition('label', $properties, 'IN')
      ->orderBy('label')
      ->execute();

    // Rows.
    $rows = [];
    while ($record = $result->fetchAssoc()) {
      $row = [];
      foreach ($record as $name => $value) {
        $row[$name] = $this->buildTableCell($name, $value);
      }
      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

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

    $breadcrumbs[$breadcrumb_id][$type] = Link::fromTextAndUrl($type, $this->getItemUrl($type));

    $parent_types = $this->manager->parseItems($item['sub_type_of']);

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

  /**
   * Build Schema.org type enumerations.
   *
   * @param string $type
   *   A Schema.org type.
   *
   * @return array
   *   A renderable array containing schema.org type enumerations.
   */
  protected function buildTypeEnumerations($type) {
    $member_types = $this->database->select('schemadotorg_types', 'types')
      ->fields('types', ['label'])
      ->condition('enumerationtype', 'https://schema.org/' . $type)
      ->orderBy('label')
      ->execute()
      ->fetchCol();
    if (!$member_types) {
      return [];
    }
    $items = [];
    foreach ($member_types as $member_type) {
      $items[] = [
        '#type' => 'link',
        '#title' => $member_type,
        '#url' => $this->getItemUrl($member_type),
      ];
    }
    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Enumeration members'),
      'items' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

  /* ************************************************************************ */
  // Fields methods.
  /* ************************************************************************ */

  /**
   * Get Schema.org type fields.
   *
   * @return array
   *   Schema.org type fields.
   */
  protected function getTypeFields() {
    return [
      'id' => $this->t('Schema.org ID'),
      'label' => $this->t('Schema.org label'),
      'drupal_label' => $this->t('Drupal label'),
      'drupal_name' => $this->t('Drupal name'),
      'comment' => $this->t('Comment'),
      'sub_type_of' => $this->t('Sub type of'),
      'enumerationtype' => $this->t('Enumeration type'),
      'equivalent_class' => $this->t('Equivalent class'),
      'sub_types' => $this->t('Sub types'),
      'supersedes' => $this->t('supersedes'),
      'superseded_by' => $this->t('Superseded by'),
      'is_part_of =>' => $this->t('Is part of'),
      'properties' => $this->t('Properties'),
    ];
  }

  /**
   * Get Schema.org property fields.
   *
   * @return array
   *   Schema.org Property fields.
   */
  protected function getPropertyFields() {
    return [
      'label' => $this->t('Schema.org label'),
      'drupal_label' => $this->t('Drupal label'),
      'drupal_name' => $this->t('Drupal name'),
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

}
