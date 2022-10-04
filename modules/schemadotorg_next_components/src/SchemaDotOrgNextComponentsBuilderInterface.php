<?php

declare(strict_types = 1);

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
   *
   * @return string
   *   An entity's Next.js component.
   */
  public function buildEntity(string $entity_type_id): string;

  /**
   * Build an entity bundle's Next.js component.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return string
   *   An entity bundle's Next.js component.
   */
  public function buildEntityBundle(string $entity_type_id, string $bundle): string;

}
