<?php

namespace Drupal\schemadotorg_report\Controller;

/**
 * Returns responses for Schema.org report heirarchy routes.
 */
class SchemaDotOrgReportHierarchyController extends SchemaDotOrgReportControllerBase {

  /**
   * Builds the Schema.org types hierarchy.
   *
   * @return array
   *   A renderable array containing Schema.org types hierarchy.
   */
  public function index($type = 'Thing') {
    if ($type === 'DataTypes') {
      $types = $this->database->select('schemadotorg_types', 'types')
        ->fields('types', ['label'])
        ->condition('sub_type_of', '')
        ->condition('label', ['True', 'False', 'Thing'], 'NOT IN')
        ->orderBy('label')
        ->execute()
        ->fetchCol();
      $ignored_types = [];
      return $this->buildItemsRecursive($types, $ignored_types);
    }
    else {
      $ignored_types = ['Intangible', 'Enumeration', 'StructuredValue'];
      $ignored_types = array_combine($ignored_types, $ignored_types);
      $count = count($this->manager->getAllTypeChildren($type, ['label'], $ignored_types));

      $build = [];
      $build['info'] = $this->buildInfo($type, $count);
      $build['hierarchy'] = $this->buildItemsRecursive([$type], $ignored_types);
      return $build;
    }
  }

  /**
   * Build info.
   *
   * @param string $type
   *   A Schema.org type .
   * @param int $count
   *   The item count to display.
   *
   * @return array
   *   A renderable array containing item count info.
   */
  protected function buildInfo($type, $count) {
    $t_args = ['@count' => $count];

    switch ($type) {
      case 'Thing':
        $info = $this->t('@count things', $t_args);
        break;

      case 'Intangible':
        $info = $this->t('@count intangibles', $t_args);
        break;

      case 'Enumeration':
        $info = $this->t('@count enumerations', $t_args);
        break;

      case 'StructuredValue':
        $info = $this->t('@count structured values', $t_args);
        break;

      default:
        $info = $this->t('@count items', $t_args);
        break;
    }
    return [
      '#markup' => $info,
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

}
