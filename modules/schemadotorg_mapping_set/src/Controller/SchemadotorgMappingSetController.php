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
   * Builds the response.
   */
  public function build() {
    // Header.
    $header = [
      ['data' => $this->t('Title'), 'width' => '15%'],
      ['data' => $this->t('Name'), 'width' => '15%'],
      ['data' => $this->t('Setup'), 'width' => '10%'],
      ['data' => $this->t('Types'), 'width' => '50%'],
      ['data' => $this->t('Operations'), 'width' => '10%'],
    ];

    // Rows.
    $rows = [];
    $mapping_sets = $this->config('schemadotorg_mapping_set.settings')->get('sets');
    foreach ($mapping_sets as $name => $mapping_set) {
      $is_setup = $this->schemaMappingSetManager->isSetup($name);

      $operations = [];
      if (!$is_setup) {
        $operations['setup'] = $this->t('Setup types');
      }
      else {
        $operations['generate'] = $this->t('Generate content');
        $operations['kill'] = $this->t('Kill content');
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

      $row = [];
      $row[] = $mapping_set['label'];
      $row[] = $name;
      $row[] = $is_setup ? $this->t('Yes') : $this->t('No');
      $row[] = implode(', ', $mapping_set['types']);
      $row[] = [
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
