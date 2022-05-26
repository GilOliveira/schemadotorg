<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org JSON-LD builder interface.
 */
interface SchemaDotOrgJsonLdBuilderInterface {

  /**
   * Build JSON-LD for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   An entity.
   *
   * @return array|bool
   *   The JSON-LD for an entity.
   */
  public function build(EntityInterface $entity = NULL);

  /**
   * Build JSON-LD for an entity that is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return array|bool
   *   The JSON-LD for an entity that is mapped to a Schema.org type
   *   or FALSE if the entity is not mapped to a Schema.org type.
   */
  public function buildEntity(EntityInterface $entity);

}
