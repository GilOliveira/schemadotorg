<?php

namespace Drupal\schemadotorg\Utilty;

/**
 * Schema.org string helper methods.
 *
 * @see https://en.wikipedia.org/wiki/Naming_convention_(programming)#Examples_of_multiple-word_identifier_formats
 */
class SchemaDotOrgStringHelper {

  /**
   * Convert camel case (camelCase) to snake case (snake_case).
   *
   * @param string $string
   *   A camel case string.
   *
   * @return string
   *   The camel case string converted to snake case.
   */
  public static function camelCaseToSnakeCase($string) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2', $string));
  }

  /**
   * Convert variable (camelCase or snake_case) to a label.
   *
   * @param string $string
   *   A variable.
   *
   * @return string
   *   Variable converted to text.
   */
  public static function toLabel($string) {
    $text = preg_replace('/([a-z])([A-Z])/', '\1_\2', $string);
    $text = str_replace('_', ' ', $text);
    $text = strtolower($text);
    return ucfirst($text);
  }

}
