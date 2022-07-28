<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org schema type builder interface.
 */
interface SchemaDotOrgSchemaTypeBuilderInterface {

  /**
   * Gets Schema.org type or property URL.
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
   * @param string|array $text
   *   A string of comma delimited items (types or properties).
   * @param array $options
   *   Link options which include:
   *   - attributes.
   *
   * @return array
   *   An array of links to Schema.org items (types or properties).
   */
  public function buildItemsLinks($text, array $options = []);

  /**
   * Build Schema.org type tree as an item list.
   *
   * @param array $tree
   *   An array of Schema.org type tree.
   * @param array $options
   *   Link options which include:
   *   - base_path.
   *   - attributes.
   *
   * @return array
   *   A renderable array containing Schema.org type tree as an item list.
   */
  public function buildTypeTree(array $tree, array $options = []);

  /**
   * Format Schema.org type or property comment.
   *
   * @param string $comment
   *   The Schema.org type or property comment.
   * @param array $options
   *   The comment's link options which include:
   *   - base_path.
   *   - attributes.
   *
   * @return string
   *   Formatted Schema.org type or property comment with links to details.
   */
  public function formatComment($comment, array $options = []);

}
