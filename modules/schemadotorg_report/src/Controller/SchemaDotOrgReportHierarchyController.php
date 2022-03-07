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
      $tree = $this->schemaDataTypeManager->getTypeTree($types);
      $count = count($this->schemaDataTypeManager->getDataTypes());
    }
    else {
      $ignored_types = ['Intangible', 'Enumeration', 'StructuredValue'];
      $ignored_types = array_combine($ignored_types, $ignored_types);
      unset($ignored_types[$type]);
      $tree = $this->schemaDataTypeManager->getTypeTree($type);
      $count = count($this->schemaDataTypeManager->getAllTypeChildren($type, ['label'], $ignored_types));
    }
    $build = [];
    $build['info'] = $this->buildInfo($type, $count);
    $build['tree'] = $this->buildTypeTreeRecursive($tree);
    return $build;
  }

}
