<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org JSON-LD builder interface.
 */
interface SchemaDotOrgJsonLdBuilderInterface {

  /**
   * Build JSON-LD for an entity that is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param array $options
   *   Options for building JSON-LD for an entity.
   *
   *   Options include:
   *   - context: Boolean to set the @context property. Default to TRUE.
   *
   * @return array|bool
   *   The JSON-LD for an entity that is mapped to a Schema.org type
   *   or FALSE if the entity is not mapped to a Schema.org type.
   */
  public function build(EntityInterface $entity, array $options = []);

}
