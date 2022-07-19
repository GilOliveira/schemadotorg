<?php

namespace Drupal\schemadotorg_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Returns responses for Schema.org export routes.
 */
class SchemaDotOrgExportController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns response for Schema.org mapping export request.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed HTTP response containing a Schema.org mapping CSV export.
   *
   * @see http://obtao.com/blog/2013/12/export-data-to-a-csv-file-with-symfony/
   */
  public function index() {
    $response = new StreamedResponse(function () {
      $handle = fopen('php://output', 'r+');

      // Header.
      fputcsv($handle, [
        'entity_type',
        'bundle',
        'schema_type',
        'schema_subtyping',
        'schema_properties',
      ]);

      // Rows.
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
      $mappings = $this->entityTypeManager->getStorage('schemadotorg_mapping')->loadMultiple();
      foreach ($mappings as $mapping) {
        fputcsv($handle, [
          $mapping->getTargetEntityTypeId(),
          $mapping->getTargetBundle(),
          $mapping->getSchemaType(),
          ($mapping->getSchemaPropertyFieldName('subtype')) ? $this->t('Yes') : $this->t('No'),
          implode('; ', $mapping->getSchemaProperties()),
        ]);
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="schemadotorg.csv"');
    return $response;
  }

}
