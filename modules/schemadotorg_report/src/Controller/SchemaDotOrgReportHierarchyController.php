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
    }
    else {
      $types = [$type];
      $ignored_types = ['Intangible', 'Enumeration', 'StructuredValue'];
      $ignored_types = array_combine($ignored_types, $ignored_types);
    }

    return $this->buildItemsRecursive($types, $ignored_types);
  }

}
