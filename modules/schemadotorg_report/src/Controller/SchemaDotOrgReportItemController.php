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
   * @param string $id
   *   The Schema.org type of property ID.
   *
   * @return array
   *   A renderable array containing a Schema.org type or property item.
   */
  public function index($id = '') {
    if ($id === '') {
      return $this->about();
    }
    elseif ($this->schemaDotOrgManager->isType($id)) {
      return $this->item('types', $id);
    }
    elseif ($this->schemaDotOrgManager->isProperty($id)) {
      return $this->item('properties', $id);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   The Schema.org type of property ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function title($id) {
    if (empty($id)) {
      return $this->t('Schema.org: About');
    }

    if ($this->schemaDotOrgManager->isDataType($id)) {
      $type = $this->t('Data type');
    }
    elseif ($this->schemaDotOrgManager->isEnumerationType($id)) {
      $type = $this->t('Enumeration type');
    }
    elseif ($this->schemaDotOrgManager->isEnumerationValue($id)) {
      $type = $this->t('Enumeration value');
    }
    elseif ($this->schemaDotOrgManager->isType($id)) {
      $type = $this->t('Type');
    }
    else {
      $type = $this->t('Property');
    }

    $t_args = ['@id' => $id, '@type' => $type];
    return $this->t('Schema.org: @id (@type)', $t_args);
  }

  /**
   * Build Schema.org about page.
   *
   * @return array
   *   A renderable array containing Schema.org about page.
   */
  protected function about() {
    $build = [];

    // Introduction.
    $introduction = '<p>' . $this->t('<a href="https://Schema.org/">Schema.org</a> is a collaborative, community activity with a mission to create, maintain, and promote schemas for structured data on the Internet, on web pages, in email messages, and beyond.') . '</p>'
      . '<p>' . $this->t('Schema.org vocabulary can be used with many different encodings, including RDFa, Microdata and JSON-LD. These vocabularies cover entities, relationships between entities and actions, and can easily be extended through a well-documented extension model. Over 10 million sites use Schema.org to markup their web pages and email messages. Many applications from Google, Microsoft, Pinterest, Yandex and others already use these vocabularies to power rich, extensible experiences.') . '</p>'
      . '<p>' . $this->t('Founded by Google, Microsoft, Yahoo and Yandex, Schema.org vocabularies are developed by an open community process, using the public-schemaorg@w3.org mailing list and through GitHub.') . '</p>'
      . '<p>' . $this->t('A shared vocabulary makes it easier for webmasters and developers to decide on a schema and get the maximum benefit for their efforts. It is in this spirit that the founders, together with the larger community have come together - to provide a shared collection of schemas.') . '</p>';
    $build['introduction'] = ['#markup' => $introduction];

    // Divider.
    $build['divider'] = ['#markup' => '<hr/>'];

    // Description top.
    $t_args = [
      ':type_href' => Url::fromRoute('schemadotorg_reports.types')->toString(),
      ':properties_href' => Url::fromRoute('schemadotorg_reports.properties')->toString(),
      ':things_href' => Url::fromRoute('schemadotorg_reports.types.things')->toString(),
    ];
    $description_top = $this->t('The schemas are a set of <a href=":types_href">types</a>, each associated with a set of <a href=":properties_href">properties</a>.', $t_args);
    $description_top .= ' ' . $this->t('The types are arranged in a <a href=":things_href">hierarchy</a>.', $t_args);
    $build['description'] = ['#markup' => $description_top];

    // Types.
    $build['types'] = $this->getFilterForm('types');

    // Description bottom.
    $description_bottom = '<p>' . $this->t('Or you can jump directly to a commonly used type:') . '</p>';
    $description_bottom .= '<ul>';
    $description_bottom .= '<li>' . $this->t('Creative works: <a title="CreativeWork" href="/CreativeWork">CreativeWork</a>, <a title="Book" href="/Book">Book</a>, <a title="Movie" href="/Movie">Movie</a>, <a title="MusicRecording" href="/MusicRecording">MusicRecording</a>, <a title="Recipe" href="/Recipe">Recipe</a>, <a title="TVSeries" href="/TVSeries">TVSeries</a> ...') . '</li>';
    $description_bottom .= '<li>' . $this->t('Embedded non-text objects: <a title="AudioObject" href="/AudioObject">AudioObject</a>, <a title="ImageObject" href="/ImageObject">ImageObject</a>, <a title="VideoObject" href="/VideoObject">VideoObject</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Event" href="/Event">Event</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a href="meddocs.html">Health and medical types</a>: notes on the health and medical types under <a title="MedicalEntity" href="/MedicalEntity">MedicalEntity</a>.') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Organization" href="/Organization">Organization</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Person" href="/Person">Person</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Place" href="/Place">Place</a>, <a title="LocalBusiness" href="/LocalBusiness">LocalBusiness</a>, <a title="Restaurant" href="/Restaurant">Restaurant</a> ...') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Product" href="/Product">Product</a>, <a title="Offer" href="/Offer">Offer</a>, <a title="AggregateOffer" href="/AggregateOffer">AggregateOffer</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Review" href="/Review">Review</a>, <a title="AggregateRating" href="/AggregateRating">AggregateRating</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Action" href="/Action">Action</a>') . '</li>';
    $description_bottom .= '</ul>';
    $path = Url::fromRoute('schemadotorg_reports')->toString();
    $build[] = ['#markup' => str_replace('href="/', 'href="' . $path . '/', $description_bottom)];
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
  protected function item($table, $id) {
    // Fields.
    $fields = ($table === 'types')
      ? $this->getTypeFields()
      : $this->getPropertyFields();

    // Item.
    $item = $this->schemaDotOrgManager->getItem($table, $id);

    // Item.
    $build = [];
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
          $properties = $this->schemaDotOrgManager->parseIds($value);
          $build[$name] = [
            '#type' => 'details',
            '#title' => $label,
            '#open' => TRUE,
            'items' => $this->buildTypeProperties($properties),
          ];
          break;

        default:
          $build[$name]['links'] = $this->buildItemsLinks($value);
      }
    }

    // Custom fields.
    if ($table === 'types') {
      // Parents.
      $build['parents'] = [
        '#weight' => '-10',
        '#suffix' => '<hr/>',
        'breadcrumbs' => $this->buildTypeBreadcrumbs($id),
      ];

      // Subtype.
      if ($item['sub_types']) {
        $subtypes = $this->schemaDotOrgManager->parseIds($item['sub_types']);
        $tree = $this->schemaDotOrgManager->getTypeTree($subtypes);
        $build['sub_types_hierarchy'] = [
          '#type' => 'details',
          '#title' => $this->t('More specific types'),
          'items' => $this->buildTypeTreeRecursive($tree),
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
    $build = [];
    $breadcrumbs = $this->schemaDotOrgManager->getTypeBreadcrumbs($type);
    foreach ($breadcrumbs as $breadcrumb_path => $breadcrumb) {
      array_walk($breadcrumb, function (&$type) {
        $type = Link::fromTextAndUrl($type, $this->getItemUrl($type));
      });
      $build[$breadcrumb_path] = [
        '#theme' => 'breadcrumb',
        '#links' => $breadcrumb,
      ];
    }
    return $build;
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
    $enumerations = $this->schemaDotOrgManager->getEnumerations($type);
    if (!$enumerations) {
      return [];
    }

    array_walk($enumerations, function (&$enumeration) {
      $enumeration = Link::fromTextAndUrl($enumeration, $this->getItemUrl($enumeration))->toRenderable();
    });

    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Enumeration members'),
      'items' => [
        '#theme' => 'item_list',
        '#items' => $enumerations,
      ],
    ];
  }

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
      'subproperties' => $this->t('Sub properties'),
      'domain_includes' => $this->t('Domain includes'),
      'range_includes' => $this->t('Range includes'),
      'inverse_of' => $this->t('Inverse of'),
      'supersedes' => $this->t('Supersedes'),
      'superseded_by' => $this->t('Superseded by'),
      'is_part_of' => $this->t('Is part of'),
    ];
  }

}
