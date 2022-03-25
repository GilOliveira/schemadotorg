<?php

namespace Drupal\schemadotorg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Schema.org autocomplete routes.
 */
class SchemaDotOrgAutocompleteController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Returns response for Schema.org  (types or properties) autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $table
   *   Types or properties table name.
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

}
