<?php

namespace Drupal\schemadotorg_taxonomy;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org taxonomy manager interface.
 */
interface SchemaDotOrgTaxonomyManagerInterface {

  /**
   * Alter Schema.org JSON-LD.
   *
   * @param array $data
   *   Schema.org type data.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function alter(array &$data, EntityInterface $entity);

}
