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
    $references = $this->schemaReferences->getReferences();

    $build = [];

    // About.
    if ($references['about']) {
      $items = [];
      foreach ($references['about'] as $uri => $title) {
        $host = parse_url($uri, PHP_URL_HOST);
        $items[] = [
          '#type' => 'link',
          '#title' => $title,
          '#url' => Url::fromUri($uri),
          '#suffix' => ' (' . $host . ')',
        ];
      }
      $build['about'] = [
        '#theme' => 'item_list',
        '#title' => [
          '#type' => 'link',
          '#title' => $this->t('About'),
          '#url' => Url::fromRoute('schemadotorg_report'),
        ],
        '#items' => $items,
      ];
    }

    // Types.
    if ($references['types']) {
      foreach ($references['types'] as $type => $uris) {
        $items = [];
        foreach ($uris as $uri => $title) {
          $host = parse_url($uri, PHP_URL_HOST);
          $items[] = [
            '#type' => 'link',
            '#title' => $title,
            '#url' => Url::fromUri($uri),
            '#suffix' => ' (' . $host . ')',
          ];
        }
        $build['types'][$type] = [
          '#theme' => 'item_list',
          '#title' => [
            '#type' => 'link',
            '#title' => $type,
            '#url' => Url::fromRoute('schemadotorg_report', ['id' => $type]),
          ],
          '#items' => $items,
        ];
      }
    }

    return $build;
  }

}
