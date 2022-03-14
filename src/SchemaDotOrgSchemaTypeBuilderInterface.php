<?php

namespace Drupal\schemadotorg;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Schema.org schema type builder interface.
 */
interface SchemaDotOrgSchemaTypeBuilderInterface {

  /**
   * Get Schema.org type or property URL.
   *
   * @param string $id
   *   Type or property ID.
   *
   * @return \Drupal\Core\Url
   *   Schema.org type or property URL.
   */
  public function getItemUrl($id);

  /**
   * Build links to Schema.org items (types or properties).
   *
   * @param string $text
   *   A string of comma delimited items (types or properties).
   *
   * @return array
   *   An array of links to Schema.org items (types or properties).
   */
  public function buildItemsLinks($text);

  /**
   * Build Schema.org type tree as an item list recursively.
   *
   * @param array $tree
   *   An array of Schema.org type tree.
   *
   * @return array
   *   A renderable array containing Schema.org type tree as an item list.
   *
   * @see \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManager::getTypesChildrenRecursive
   */
  public function buildTypeTreeRecursive(array $tree);

  /**
   * Format Schema.org type or property comment.
   *
   * @param string $comment
   *   A comment.
   * @param string $base_path
   *   Base patt for all Schema.org types and properties.
   *   Default to https://schema.org.
   *
   * @return string
   *   Formatted Schema.org type or property comment with links to details.
   */
  public function formatComment($comment, $base_path = 'https://schema.org');

}
