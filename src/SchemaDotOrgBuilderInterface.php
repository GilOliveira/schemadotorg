<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org builder interface.
 */
interface SchemaDotOrgBuilderInterface {

  /**
   * Create type vocabularies.
   *
   * @param string $type
   *   A Schema.org types.
   */
  public function createTypeVocabulary($type);

}
