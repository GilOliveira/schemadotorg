<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_custom_field;

/**
 * Schema.org Custom Field builder interface.
 */
interface SchemaDotOrgCustomFieldBuilderInterface {

  /**
   * Preprocess variables for customfield.html.twig.
   *
   * Appends units to custom field values.
   *
   * @param array $variables
   *   Variables for customfield.html.twig.
   */
  public function preprocessCustomField(array &$variables): void;

}
