<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base controller for Schema.org report routes.
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
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->formBuilder = $container->get('form_builder');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    return $instance;
  }

  /**
   * Gets Schema.org types or properties filter form.
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
      '#prefix' => '<p>',
      '#suffix' => '</p>',
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
      case 'comment':
        $options = ['base_path' => Url::fromRoute('schemadotorg_report')->toString()];
        return ['data' => ['#markup' => $this->schemaTypeBuilder->formatComment($value, $options)]];

      default:
        $links = $this->schemaTypeBuilder->buildItemsLinks($value);
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
   * Build a reference links.
   *
   * @param array $links
   *   An array of link titles and uris.
   *
   * @return array
   *   A renderable containing reference links.
   */
  protected function buildReportLinks(array $links) {
    $items = [];
    foreach ($links as $link) {
      $host = parse_url($link['uri'], PHP_URL_HOST);
      $items[] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => Url::fromUri($link['uri']),
        '#suffix' => ' (' . $host . ')',
      ];
    }
    return $items;
  }

}
