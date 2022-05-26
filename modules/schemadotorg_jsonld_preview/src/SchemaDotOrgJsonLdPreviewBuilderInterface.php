<?php

namespace Drupal\schemadotorg_jsonld_preview;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org JSON-LD preview builder interface.
 */
interface SchemaDotOrgJsonLdPreviewBuilderInterface {

  /**
   * Build JSON-LD preview for the current route.
   *
   * @return array
   *   The JSON-LD preview for the current route.
   */
  public function build();

}
