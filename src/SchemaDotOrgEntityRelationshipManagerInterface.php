<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org entity relationship manager interface.
 */
interface SchemaDotOrgEntityRelationshipManagerInterface {

  /**
   * Repair relationships.
   *
   * @return array
   *   An array of messages.
   */
  public function repair();

}
