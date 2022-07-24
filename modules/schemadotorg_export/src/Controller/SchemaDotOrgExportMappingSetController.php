<?php

namespace Drupal\schemadotorg_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Returns responses for Schema.org mapping set export.
 */
class SchemaDotOrgExportMappingSetController extends ControllerBase {

  /**
   * The Schema.org mapping set manager service.
   *
   * @var \Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface
   */
  protected $schemaMappingSetManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->schemaMappingSetManager = $container->get('schemadotorg_mapping_set.manager');
    return $instance;
  }

  /**
   * Returns response for Schema.org mapping set request.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed HTTP response containing a Schema.org mapping set CSV export.
   */
  public function index() {
    $response = new StreamedResponse(function () {
      $handle = fopen('php://output', 'r+');

      // Header.
      fputcsv($handle, [
        'title',
        'name',
        'types',
      ]);

      // Rows.
      $mapping_sets = $this->config('schemadotorg_mapping_set.settings')->get('sets') ?? [];
      foreach ($mapping_sets as $name => $mapping_set) {
        fputcsv($handle, [
          $mapping_set['label'],
          $name,
          implode('; ', $mapping_set['types']),
        ]);
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="schemadotorg_mapping_set.csv"');
    return $response;
  }

}
