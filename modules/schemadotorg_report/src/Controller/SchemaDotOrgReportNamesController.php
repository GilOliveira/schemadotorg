<?php

namespace Drupal\schemadotorg_report\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org report names routes.
 */
class SchemaDotOrgReportNamesController extends SchemaDotOrgReportControllerBase {

  /**
   * The Schema.org Names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaDotOrgNames;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->schemaDotOrgNames = $container->get('schemadotorg.names');
    return $instance;
  }

  /**
   * Builds the Schema.org names overview or table.
   *
   * @return array
   *   A renderable array containing Schema.org names overview or table.
   */
  public function index($display) {
    if ($display === 'overview') {
      return $this->overview();
    }
    else {
      return $this->table($display);
    }
  }

  /**
   * Builds the Schema.org names overview.
   *
   * @return array
   *   A renderable array containing Schema.org names overview.
   */
  public function overview() {
    $names = [];
    $abbreviated = [];
    $truncated = [];

    $prefixes = [];
    $suffixes = [];
    $words = [];

    $tables = ['types', 'properties'];
    foreach ($tables as $table) {
      $labels = $this->database->select('schemadotorg_' . $table, 't')
        ->fields('t', ['label'])
        ->orderBy('label')
        ->execute()
        ->fetchCol();

      foreach ($labels as $label) {
        $max_length = $this->schemaDotOrgNames->getNameMaxLength($table);
        $name = $this->schemaDotOrgNames->camelCaseToSnakeCase($label);
        $names[$name] = $label;

        $drupal_name = $this->schemaDotOrgNames->toDrupalName($table, $label);
        $drupal_name_length = strlen($drupal_name);
        if ($drupal_name_length > $max_length) {
          $truncated[$name] = [
            'label' => $label,
            'name' => $drupal_name,
            'length' => $drupal_name_length,
            'max_length' => $max_length,
          ];
        }
        elseif ($name !== $drupal_name) {
          $abbreviated[$name] = $drupal_name;
        }

        $name_parts = explode('_', $name);
        if (count($name_parts) > 1) {
          $prefix_parts = array_slice($name_parts, 0, 2);
          $one_word_prefix = $prefix_parts[0];
          $prefixes += [$one_word_prefix => 0];
          $prefixes[$one_word_prefix]++;

          $two_word_prefix = implode('_', $prefix_parts);
          $prefixes += [$two_word_prefix => 0];
          $prefixes[$two_word_prefix]++;

          $suffix = end($name_parts);
          $suffixes += [$suffix => 0];
          $suffixes[$suffix]++;
        }

        reset($name_parts);
        foreach ($name_parts as $name_part) {
          $words += [$name_part => 0];
          $words[$name_part]++;
        }
      }
    }

    $build = [];
    $build['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General summary'),
      '#open' => TRUE,
    ];
    $build['general']['names'] = [
      '#type' => 'item',
      '#title' => $this->t('Total names'),
      'value' => ['#plain_text' => count($names)],
    ];
    $build['general']['abbreviated'] = [
      '#type' => 'item',
      '#title' => $this->t('Total abbreviated names'),
      'value' => ['#plain_text' => count($abbreviated)],
    ];
    if ($truncated) {
      $build['general']['truncated'] = [
        '#type' => 'item',
        '#title' => $this->t('Total truncated names'),
        'value' => ['#plain_text' => count($truncated)],
        'table' => [
          '#type' => 'table',
          '#header' => [
            $this->t('Label'),
            $this->t('Name'),
            $this->t('Length'),
            $this->t('Max length'),
          ],
          '#rows' => $truncated,
        ],
      ];
    }
    $config = $this->config('schemadotorg.settings');
    $build['usage'] = [
      '#type' => 'details',
      '#title' => $this->t('Usage summary'),
      '#open' => TRUE,
    ];
    $build['usage']['words'] = $this->buildWordUsage(
      $this->t('Words'),
      $this->t('Word'),
      $words,
      $config->get('names.abbreviations')
    );
    $build['usage']['prefixes'] = $this->buildWordUsage(
      $this->t('Prefixes'),
      $this->t('Prefix'),
      $prefixes,
      $config->get('names.prefixes')
    );
    $build['usage']['suffixes'] = $this->buildWordUsage(
      $this->t('Suffixes'),
      $this->t('Suffix'),
      $suffixes,
      $config->get('names.suffixes')
    );

    return $build;
  }

  /**
   * Build word usage.
   *
   * @param string $title
   *   Details title.
   * @param string $label
   *   Header label.
   * @param array $words
   *   Words.
   * @param array $abbreviations
   *   Abbreviations.
   *
   * @return array
   *   A renderable array containing word usage.
   */
  protected function buildWordUsage($title, $label, array $words, array $abbreviations) {
    // Remove words that are less than 5 characters.
    $words = array_filter($words, function ($word) {
      return strlen($word) > 5;
    }, ARRAY_FILTER_USE_KEY);

    // Remove words that are only used once.
    $words = array_filter($words, function ($usage) {
      return $usage > 1;
    });

    // Sort by usage.
    asort($words, SORT_NUMERIC);
    $words = array_reverse($words);

    // Header.
    $header = [
      'word' => $label,
      'word_usage' => $this->t('Used'),
      'abbreviation' => $this->t('Abbreviation'),
    ];

    // Rows.
    $rows = [];
    foreach ($words as $word => $usage) {
      $row = [];
      $row['word'] = $word;
      $row['word_usage'] = $usage;
      $row['abbreviation'] = $abbreviations[$word] ?? '';
      $rows[] = $row;
    }

    $replacements_rows = [];
    foreach ($abbreviations as $source => $abbreviation) {
      $replacements_rows[] = [$source, $abbreviation];
    }
    $build = [
      '#type' => 'details',
      '#title' => $title,
    ];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $build['replacements'] = [
      '#type' => 'details',
      '#title' => $this->t('Replacements'),
      'table' => [
        '#type' => 'table',
        '#header' => [$label, $this->t('Replacement')],
        '#rows' => $replacements_rows,
      ],
    ];
    return $build;
  }

  /**
   * Builds the Schema.org names table.
   *
   * @return array
   *   A renderable array containing Schema.org names table.
   */
  public function table($display) {
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
      $schema_ids = $this->database->select('schemadotorg_' . $table, $table)
        ->fields($table, ['label'])
        ->orderBy('label')
        ->execute()
        ->fetchCol();
      $max_length = $this->schemaDotOrgNames->getNameMaxLength($table);
      foreach ($schema_ids as $schema_id) {
        $schema_item = ($table === 'types') ? $this->t('Type') : $this->t('Properties');
        $schema_label = $this->schemaDotOrgNames->toDrupalLabel($table, $schema_id);
        $original_name = $this->schemaDotOrgNames->camelCaseToSnakeCase($schema_id);
        $original_name_length = strlen($original_name);
        $drupal_name = $this->schemaDotOrgNames->toDrupalName($table, $schema_id);
        $drupal_name_length = strlen($drupal_name);

        $row = [];
        if (!$is_schema_item) {
          $row['schema_item'] = $schema_item;
        }
        $row['schema_id'] = [
          'data' => [
            '#type' => 'link',
            '#title' => $schema_id,
            '#url' => $this->schemaTypeBuilder->getItemUrl($schema_id),
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
        if ($display !== 'abbreviations' || $class) {
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
