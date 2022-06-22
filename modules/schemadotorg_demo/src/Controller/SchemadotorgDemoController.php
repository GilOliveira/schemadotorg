<?php

namespace Drupal\schemadotorg_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org Blueprints Demo routes.
 */
class SchemadotorgDemoController extends ControllerBase {

  /**
   * The Schema.org demo manager service.
   *
   * @var \Drupal\schemadotorg_demo\SchemaDotOrgDemoManagerInterface
   */
  protected $schemaDemoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->schemaDemoManager = $container->get('schemadotorg_demo.manager');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function build() {
    // Header.
    $header = [
      ['data' => $this->t('Title'), 'width' => '20%'],
      ['data' => $this->t('Name'), 'width' => '10%'],
      ['data' => $this->t('Setup'), 'width' => '20%'],
      ['data' => $this->t('Types'), 'width' => '50%'],
    ];

    // Rows.
    $rows = [];
    $demos = $this->config('schemadotorg_demo.settings')->get('demos');
    foreach ($demos as $name => $demo) {
      $is_setup = $this->schemaDemoManager->isSetup($name);

      $row = [];
      $row[] = $demo['label'];
      $row[] = $name;
      $row[] = $is_setup ? $this->t('Yes') : $this->t('No');
      $row[] = implode(', ', $demo['types']);
      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
