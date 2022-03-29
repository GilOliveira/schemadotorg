<?php

namespace Drupal\Tests\schemadotorg\Traits;

use Drupal\Component\Render\MarkupInterface;

/**
 * Provides convenience methods for Schema.org assertions.
 */
trait SchemaDotOrgTestTrait {

  /**
   * Convert all render(able) markup into strings.
   *
   * @param array $elements
   *   An associative array of elements.
   */
  protected function convertMarkupToStrings(array &$elements) {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        $this->convertMarkupToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
    }
  }

}
