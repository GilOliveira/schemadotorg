<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Schema.org names service.
 *
 * @see https://www.allacronyms.com/
 */
class SchemaDotOrgNames implements SchemaDotOrgNamesInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SchemaDotOrgNames object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldPrefix() {
    return $this->getSettingsConfig()->get('field_prefix');
  }

  /**
   * {@inheritdoc}
   */
  public function getNameMaxLength($table) {
    return ($table === 'properties')
      ? 32 - strlen($this->getFieldPrefix())
      : 32;
  }

  /**
   * {@inheritdoc}
   */
  public function snakeCaseToUpperCamelCase($string) {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
  }

  /**
   * {@inheritdoc}
   */
  public function snakeCaseToCamelCase($string) {
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
  }

  /**
   * {@inheritdoc}
   */
  public function camelCaseToSnakeCase($string) {
    $intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', '_$0', $string);
    $snake_case = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1_$2', $intermediate);
    return strtolower($snake_case);
  }

  /**
   * {@inheritdoc}
   */
  public function camelCaseToTitleCase($string) {
    // CamelCase to Title Case PHP Regex.
    // @see https://gist.github.com/justjkk/1402061
    $intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', ' $0', $string);
    $title = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1 $2', $intermediate);

    // Custom words.
    $custom_words = $this->getNamesConfig()->get('custom_words');
    foreach ($custom_words as $search => $replace) {
      $title = str_replace($search, $replace, $title);
    }

    // Acronyms.
    $acronyms = $this->getNamesConfig()->get('acronyms');
    $title = preg_replace_callback('/(\b)(' . implode('|', $acronyms) . ')(\b)/i', function ($matches) {
      return $matches[1] . strtoupper($matches[2]) . $matches[3];
    }, $title);

    // Minor words.
    $minor_words = $this->getNamesConfig()->get('minor_words');
    $title = preg_replace_callback('/ (' . implode('|', $minor_words) . ')(\b)/i', function ($matches) {
      return ' ' . strtolower($matches[1]) . $matches[2];
    }, $title);

    return ucfirst($title);
  }

  /**
   * {@inheritdoc}
   */
  public function camelCaseToSentenceCase($string) {
    $sentence = $this->camelCaseToTitleCase($string);
    $sentence = preg_replace_callback('/ ([A-Z])([a-z])/', function ($matches) {
      return ' ' . strtolower($matches[1]) . $matches[2];
    }, $sentence);
    return ucfirst($sentence);
  }

  /**
   * {@inheritdoc}
   */
  public function toDrupalLabel($table, $string) {
    return ($table === 'types')
      ? $this->camelCaseToTitleCase($string)
      : $this->camelCaseToSentenceCase($string);
  }

  /**
   * {@inheritdoc}
   */
  public function toDrupalName($table, $string) {
    $max_length = $this->getNameMaxLength($table);
    $drupal_name = $this->camelCaseToSnakeCase($string);

    // Custom names.
    $custom_names = $this->getNamesConfig()->get('custom_names');
    if (isset($custom_names[$drupal_name])) {
      return $custom_names[$drupal_name];
    }

    // Prefixes.
    // NOTE: Prefixes are always applied to names to ensure consistency when
    // visually scanning names.
    $prefixes = $this->getNamesConfig()->get('prefixes');
    foreach ($prefixes as $search => $replace) {
      $drupal_name = preg_replace('/^' . $search . '_/', $replace . '_', $drupal_name);
    }
    if (strlen($drupal_name) <= $max_length) {
      return $drupal_name;
    }

    // Abbreviations.
    $abbreviations = $this->getNamesConfig()->get('abbreviations');
    foreach ($abbreviations as $search => $replace) {
      $drupal_name = preg_replace('/_' . $search . '_/', '_' . $replace . '_', $drupal_name);
    }
    if (strlen($drupal_name) <= $max_length) {
      return $drupal_name;
    }

    // Suffixes.
    $suffixes = $this->getNamesConfig()->get('suffixes');
    foreach ($suffixes as $search => $replace) {
      $drupal_name = preg_replace('/_' . $search . '$/', '_' . $replace, $drupal_name);
    }

    return $drupal_name;
  }

  /**
   * Get the Schema.org settings configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The Schema.org settings configuration.
   */
  protected function getSettingsConfig() {
    return $this->configFactory->get('schemadotorg.settings');
  }

  /**
   * Get the Schema.org names configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The Schema.org names configuration.
   */
  protected function getNamesConfig() {
    return $this->configFactory->get('schemadotorg.names');
  }

}
