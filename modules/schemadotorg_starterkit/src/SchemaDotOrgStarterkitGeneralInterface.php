<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit;

/**
 * Schema.org starterkit generate interface.
 */
interface SchemaDotOrgStarterkitGeneralInterface {

  /**
   * Generate content.
   *
   * @param array $entity_types
   *   An associative array onf entity types and bundles.
   */
  function generate(array $entity_types):void;

}
