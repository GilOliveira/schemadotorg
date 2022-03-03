<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\schemadotorg\Utilty\SchemaDotOrgStringHelper;

/**
 * Returns responses for Schema.org report names routes.
 */
class SchemaDotOrgReportNamesController extends SchemaDotOrgReportControllerBase {

  /**
   * Builds the Schema.org names table.
   *
   * @return array
   *   A renderable array containing Schema.org names table.
   */
  public function index($display = '') {
    $tables = ['types', 'properties'];
    $is_schema_item = in_array($display, $tables);

    $header = [
      'schema_item' => [
        'data' => $this->t('Schema.org item'),
      ],
      'schema_id' => [
        'data' => $this->t('Schema.org ID'),
      ],
      'schema_label' => [
        'data' => $this->t('Schema.org label'),
      ],
      'original_name' => [
        'data' => $this->t('Original name'),
      ],
      'original_name_length' => [
        'data' => $this->t('#'),
      ],
      'drupal_name' => [
        'data' => $this->t('Drupal name'),
      ],
      'drupal_name_length' => [
        'data' => $this->t('#'),
      ],
    ];

    if ($is_schema_item) {
      $tables = [$display];
      unset($header['schema_item']);
    }

    $rows = [];
    foreach ($tables as $table) {
      $max_length = ($table === 'types') ? 32 : 25;
      $schema_ids = $this->database->select('schemadotorg_' . $table, $table)
        ->fields($table, ['label'])
        ->orderBy('label')
        ->execute()
        ->fetchCol();
      foreach ($schema_ids as $schema_id) {
        $schema_item = ($table === 'types') ? $this->t('Type') : $this->t('Properties');
        $schema_label = SchemaDotOrgStringHelper::camelCaseToTitleCase($schema_id);
        $original_name = SchemaDotOrgStringHelper::camelCaseToSnakeCase($schema_id);
        $original_name_length = strlen($original_name);
        $drupal_name = SchemaDotOrgStringHelper::toDrupalName($schema_id, $max_length);
        $drupal_name_length = strlen($drupal_name);

        $row = [];
        if (!$is_schema_item) {
          $row['schema_item'] = $schema_item;
        }
        $row['schema_id'] = [
          'data' => [
            '#type' => 'link',
            '#title' => $schema_id,
            '#url' => $this->getItemUrl($schema_id),
          ],
        ];
        $row['schema_label'] = $schema_label;
        $row['original_name'] = $original_name;
        $row['original_name_length'] = $original_name_length;
        $row['drupal_name'] = $drupal_name;
        $row['drupal_name_length'] = $drupal_name_length;

        if ($drupal_name_length > $max_length) {
          $class = ['color-error'];
        }
        elseif ($original_name !== $drupal_name) {
          $class = ['color-warning'];
        }
        else {
          $class = [];
        }
        if ($display !== 'warnings' || $class) {
          $rows[$schema_id] = ['data' => $row];
          $rows[$schema_id]['class'] = $class;
        }
      }
    }
    ksort($rows);

    $build = [];
    $build['info'] = $this->buildInfo($display, count($rows));
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $build;
  }

}
