<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org report references routes.
 */
class SchemaDotOrgReportReferencesController extends SchemaDotOrgReportControllerBase {

  /**
   * Builds the Schema.org references.
   *
   * @return array
   *   A renderable array containing the Schema.org references.
   */
  public function index() {
    $config = $this->config('schemadotorg_report.settings');

    $build = [];

    // About.
    $about = $config->get('about');
    if ($about) {
      $build['about'] = [
        '#theme' => 'item_list',
        '#title' => [
          '#type' => 'link',
          '#title' => $this->t('About'),
          '#url' => Url::fromRoute('schemadotorg_report'),
        ],
        '#items' => $this->buildReferenceLinks($about),
      ];
    }

    // Types.
    $types = $config->get('types');
    if ($types) {
      foreach ($types as $type => $links) {
        $build['types'][$type] = [
          '#theme' => 'item_list',
          '#title' => [
            '#type' => 'link',
            '#title' => $type,
            '#url' => Url::fromRoute('schemadotorg_report', ['id' => $type]),
          ],
          '#items' => $this->buildReferenceLinks($links),
        ];
      }
    }

    return $build;
  }

}
