<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Utility;

/**
 * Helper class Schema.org string methods.
 */
class SchemaDotOrgStringHelper {

  /**
   * Get first sentence from text.
   *
   * @param string $text
   *   The text.
   *
   * @return string
   *   The first sentence from the text.
   */
  public static function getFirstSentence(string $text): string {
    if (!$text || !str_contains($text, '.')) {
      return $text;
    }

    $escaped = [
      'e.g.' => 'e_g_',
      '...' => '|||',
    ];
    $text = str_replace(array_keys($escaped), $escaped, $text);
    $text = substr($text, 0, strpos($text, '.') + 1);
    $text = str_replace($escaped, array_keys($escaped), $text);
    return $text;
  }

}
