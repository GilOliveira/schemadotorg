<?php

namespace Drupal\schemadotorg_mapping_set\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org Blueprints Mapping Sets routes.
 */
class SchemadotorgMappingSetController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaMappingSetManager = $container->get('schemadotorg_mapping_set.manager');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function build() {
    // Header.
    $header = [
      'title' => ['data' => $this->t('Title'), 'width' => '15%'],
      'name' => ['data' => $this->t('Name'), 'width' => '15%'],
      'setup' => ['data' => $this->t('Setup'), 'width' => '10%'],
      'types' => ['data' => $this->t('Types'), 'width' => '50%'],
      'operations' => ['data' => $this->t('Operations'), 'width' => '10%'],
    ];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    // Rows.
    $rows = [];
    $mapping_sets = $this->config('schemadotorg_mapping_set.settings')->get('sets');
    foreach ($mapping_sets as $name => $mapping_set) {
      $is_setup = $this->schemaMappingSetManager->isSetup($name);

      // Operations.
      $operations = [];
      if (!$is_setup) {
        $operations['setup'] = $this->t('Setup types');
      }
      else {
        if ($this->moduleHandler()->moduleExists('devel_generate')) {
          $operations['generate'] = $this->t('Generate content');
          $operations['kill'] = $this->t('Kill content');
        }
        $operations['teardown'] = $this->t('Teardown types');
      }
      foreach ($operations as $operation => $title) {
        $operations[$operation] = [
          'title' => $title,
          'url' => Url::fromRoute(
            'schemadotorg_mapping_set.confirm_form',
            ['name' => $name, 'operation' => $operation],
          ),
        ];
      }

      // Types.
      $types = $mapping_set['types'];
      foreach ($types as $index => $type) {
        [$entity_type_id, $schema_type] = explode(':', $type);
        $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
        if ($mapping) {
          $entity_type_bundle = $mapping->getTargetEntityBundleEntity();
          $types[$index] = $entity_type_bundle->toLink($type, 'edit-form')->toString();
        }
      }

      $row = [];
      $row['title'] = $mapping_set['label'];
      $row['name'] = $name;
      $row['setup'] = $is_setup ? $this->t('Yes') : $this->t('No');
      $row['types'] = ['data' => ['#markup' => implode(', ', $types)]];
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];
      if ($is_setup) {
        $rows[] = ['data' => $row, 'class' => ['color-success']];
      }
      else {
        $rows[] = $row;
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
