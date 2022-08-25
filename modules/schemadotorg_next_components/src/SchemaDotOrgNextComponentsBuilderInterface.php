<?php

namespace Drupal\schemadotorg_next_components;

/**
 * Schema.org Next.js components builder interface.
 */
interface SchemaDotOrgNextComponentsBuilderInterface {

  /**
   * Build an entity's Next.js component.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return string
   *   An entity's Next.js component.
   */
  public function build($entity_type_id, $bundle);

}
