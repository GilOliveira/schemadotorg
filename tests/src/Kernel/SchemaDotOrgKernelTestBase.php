<?php

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Defines an abstract test base for Schema.org kernel tests.
 */
abstract class SchemaDotOrgKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg'];


  /**
   * Convert all render(able) markup into strings.
   *
   * @param array $elements
   *   An associative array of elements.
   */
  protected function convertMarkupToStrings(array &$elements) {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        self::convertMarkupToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
    }
  }

}
