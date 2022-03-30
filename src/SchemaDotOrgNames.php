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
    // We are storing a reference to the config factory and not the
    // schemadotorg configuration because this service will be initialized
    // before configuration is installed and called by Schema.org installer.
    // @see \Drupal\schemadotorg\SchemaDotOrgInstaller
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getNameMaxLength($table) {
    return ($table === 'types') ? 32 : 25;
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

    // Custom.
    $custom_titles = $this->getConfig()->get('names.custom_titles');
    foreach ($custom_titles as $search => $replace) {
      $title = str_replace($search, $replace, $title);
    }

    // Acronyms.
    $acronyms = $this->getConfig()->get('names.acronyms');
    $title = preg_replace_callback('/(\b)(' . implode('|', $acronyms) . ')(\b)/i', function ($matches) {
      return $matches[1] . strtoupper($matches[2]) . $matches[3];
    }, $title);

    // Minor words.
    $minor_words = $this->getConfig()->get('names.minor_words');
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
    $length = $this->getNameMaxLength($table);
    $drupal_name = $this->camelCaseToSnakeCase($string);

    // Custom.
    $custom = $this->getConfig()->get('names.custom_names');
    if (isset($custom[$drupal_name])) {
      return $custom[$drupal_name];
    }

    // Prefixes.
    $prefixes = $this->getConfig()->get('names.prefixes');
    foreach ($prefixes as $search => $replace) {
      $drupal_name = preg_replace('/^' . $search . '_/', $replace . '_', $drupal_name);
    }

    // Do not do any more abbreviations if the name has less than two words.
    if (substr_count($drupal_name, '_') <= 1
      && (!$length || (strlen($drupal_name) < $length))) {
      return $drupal_name;
    }

    // Suffixes.
    $suffixes = $this->getConfig()->get('names.suffixes');
    foreach ($suffixes as $search => $replace) {
      $drupal_name = preg_replace('/_' . $search . '$/', '_' . $replace, $drupal_name);
    }

    // Do not do any more abbreviations if the name is less than the limit.
    if (!$length || strlen($drupal_name) < $length) {
      return $drupal_name;
    }

    // Abbreviations.
    $abbreviations = $this->getConfig()->get('names.abbreviations');
    foreach ($abbreviations as $search => $replace) {
      $drupal_name = preg_replace('/(^|_)' . $search . '($|_)/', '\1' . $replace . '\2', $drupal_name);
    }

    return $drupal_name;
  }

  /**
   * Get the Schema.org settings configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The Schema.org settings configuration.
   */
  protected function getConfig() {
    return $this->configFactory->get('schemadotorg.settings');
  }

}
