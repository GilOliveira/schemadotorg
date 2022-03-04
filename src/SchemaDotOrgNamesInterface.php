<?php

namespace Drupal\schemadotorg;

/**
 * Schema.org names interface.
 */
interface SchemaDotOrgNamesInterface {

  /**
   * Convert camel case (camelCase) to snake case (snake_case).
   *
   * @param string $string
   *   A camel case string.
   *
   * @return string
   *   The camel case string converted to snake case.
   */
  public function camelCaseToSnakeCase($string);

  /**
   * Convert camel case (camelCase) to title case (Title Case).
   *
   * @param string $string
   *   A camel case string.
   *
   * @return string
   *   The camel case string converted to title case.
   */
  public function camelCaseToTitleCase($string);

  /**
   * Convert Schema.org type or property to Drupal machine name.
   *
   * @param string $label
   *   A Schema.org type or property.
   * @param int $length
   *   Maximum number of characters allowed for the Drupal machine name.
   *
   * @return string
   *   Schema.org type or property converted to Drupal machine name.
   */
  public function toDrupalName($label, $length = 0);

  /**
   * Get name prefixes.
   *
   * @return string[]
   *   An associative array of name prefixes.
   */
  public function getNamePrefixes();

  /**
   * Get name suffixes.
   *
   * @return string[]
   *   An associative array of name suffixes.
   */
  public function getNameSuffixes();

  /**
   * Get custom abbreviations.
   *
   * @return string[]
   *   An associative array of custom abbreviation.
   */
  public function getNameAbbreviations();

  /**
   * Get custom name abbreviations.
   *
   * @return string[]
   *   An associative array of custom name abbreviation.
   */
  public function getCustomNames();

  /**
   * Get custom titles.
   *
   * @return string[]
   *   An associative array of custom title.
   */
  public function getCustomTitles();

  /**
   * Get acronyms.
   *
   * @return string[]
   *   An array of acronyms.
   */
  public function getAcronyms();

  /**
   * Get minor words.
   *
   * @return string[]
   *   An array of minor words.
   */
  public function getMinorWords();

}
