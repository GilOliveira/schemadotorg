<?php

namespace Drupal\schemadotorg_jsonld_preview;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org JSON-LD preview builder interface.
 */
interface SchemaDotOrgJsonLdPreviewBuilderInterface {

  /**
   * Build JSON-LD preview for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   An entity.
   *
   * @return array
   *   The JSON-LD preview for an entity.
   */
  public function build(EntityInterface $entity = NULL);

}
