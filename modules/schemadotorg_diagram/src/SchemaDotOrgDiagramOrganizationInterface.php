<?php

namespace Drupal\schemadotorg_diagram;

use Drupal\node\NodeInterface;

/**
 * Schema.org diagram organization interface.
 */
interface SchemaDotOrgDiagramOrganizationInterface {

  /**
   * Build the organization's diagram.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   *
   * @return array|null
   *   The organization's diagram.
   */
  public function build(NodeInterface $node): ?array;

}
