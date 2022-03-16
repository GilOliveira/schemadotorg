<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Schema.org report table routes.
 */
class SchemaDotOrgReportTableController extends SchemaDotOrgReportControllerBase {

  /**
   * Builds the Schema.org types or properties documentation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $table
   *   Schema.org types and properties table.
   *
   * @return array
   *   A renderable array containing Schema.org types or properties
   *   documentation.
   */
  public function index(Request $request, $table) {
    $id = $request->query->get('id');

    // Header.
    $header = ($table === 'types')
      ? $this->getTypesHeader()
      : $this->getPropertiesHeader();

    // Base query.
    $base_query = $this->database->select('schemadotorg_' . $table, $table);
    $base_query->fields($table, array_keys($header));
    $base_query->orderBy('label');
    if ($id) {
      $or = $base_query->orConditionGroup()
        ->condition('label', '%' . $id . '%', 'LIKE')
        ->condition('comment', '%' . $id . '%', 'LIKE');
      $base_query->condition($or);
    }

    // Total.
    $total_query = clone $base_query;
    $count = $total_query->countQuery()->execute()->fetchField();

    // Result.
    $result_query = clone $base_query;
    $result_query = $result_query->extend(PagerSelectExtender::class)->limit(100);
    $result = $result_query->execute();

    // Rows.
    $rows = [];
    while ($record = $result->fetchAssoc()) {
      $row = [];
      foreach ($record as $name => $value) {
        $row[$name] = $this->buildTableCell($name, $value);
      }
      $rows[] = $row;
    }

    $t_args = [
      '@type' => ($table === 'types') ? $this->t('types') : $this->t('properties'),
    ];

    $build = [];
    $build['filter'] = $this->getFilterForm($table, $id);
    $build['info'] = $this->buildInfo($table, $count);
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('No @type found.', $t_args),
    ];
    $build['pager'] = ['#type' => 'pager'];
    return $build;
  }

  /**
   * Get Schema.org types table header.
   *
   * @return array[]
   *   Schema.org types table header.
   */
  protected function getTypesHeader() {
    return [
      'label' => [
        'data' => $this->t('Schema.org label'),
      ],
      'drupal_label' => [
        'data' => $this->t('Drupal label'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'drupal_name' => [
        'data' => $this->t('Drupal name'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
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

  /**
   * Get properties table header.
   *
   * @return array[]
   *   Properties table header.
   */
  protected function getPropertiesHeader() {
    return [
      'label' => [
        'data' => $this->t('Schema.org label'),
      ],
      'drupal_label' => [
        'data' => $this->t('Drupal label'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'drupal_name' => [
        'data' => $this->t('Drupal name'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
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

}