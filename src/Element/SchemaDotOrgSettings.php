<?php

/* phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint */
/* phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint */

declare(strict_types = 1);

namespace Drupal\schemadotorg\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Textarea;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Provides a form element for Schema.org Blueprints settings.
 *
 * @FormElement("schemadotorg_settings")
 */
class SchemaDotOrgSettings extends Textarea {

  /**
   * Indexed.
   */
  const INDEXED = 'indexed';

  /**
   * Indexed grouped.
   */
  const INDEXED_GROUPED = 'indexed_grouped';

  /**
   * Indexed grouped named.
   */
  const INDEXED_GROUPED_NAMED = 'indexed_grouped_named';

  /**
   * Associative.
   */
  const ASSOCIATIVE = 'associative';

  /**
   * Associative grouped.
   */
  const ASSOCIATIVE_GROUPED = 'associative_grouped';

  /**
   * Associative grouped names.
   */
  const ASSOCIATIVE_GROUPED_NAMED = 'associative_grouped_named';

  /**
   * Links.
   */
  const LINKS = 'links';

  /**
   * Links grouped.
   */
  const LINKS_GROUPED = 'links_grouped';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processSchemaDotOrgSettings'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#settings_type' => static::INDEXED,
      '#group_name' => 'label',
      '#array_name' => 'items',
      '#settings_description' => TRUE,
      '#settings_format' => '',
      '#description' => '',
      '#description_link' => '',
      '#attributes' => [
        'wrap' => 'off',
      ],
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return ($input === FALSE)
    ? static::convertSettingsToElementDefaultValue($element)
    : NULL;
  }

  /**
   * Processes a 'schemadotorg_settings' element.
   */
  public static function processSchemaDotOrgSettings(&$element, FormStateInterface $form_state, &$complete_form) {
    // Append Schema.org browse types or properties link to the description.
    $link_table = $element['#description_link'];
    if (in_array($link_table, ['types', 'properties'])
      && \Drupal::moduleHandler()->moduleExists('schemadotorg_report')) {
      $link_text = ($link_table === 'types')
        ? t('Browse Schema.org types.')
        : t('Browse Schema.org properties.');
      $link_url = Url::fromRoute("schemadotorg_report.$link_table");
      $element['#description'] .= (!empty($element['#description'])) ? '<br/>' : '';
      $element['#description'] .= Link::fromTextAndUrl($link_text, $link_url)->toString();
      $element['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';
    }

    // Append settings description with or without settings format.
    if ($element['#settings_description']) {
      $element['#description'] .= (!empty($element['#description'])) ? '<br/><br/>' : '';
      $format = static::getSettingsFormat($element);
      if ($format) {
        // Format and emphasize each format example.
        $code_examples = explode(' or ', $format);
        $code_prefix = '<code><strong>';
        $code_separator = '</strong></code> ' . t('or') . ' <code><strong>';
        $code_suffix = '</strong></code>';
        $code_example = Markup::create($code_prefix . implode($code_separator, $code_examples) . $code_suffix);

        $element['#description'] .= t('Enter one value per line, in the format @code.', ['@code' => $code_example]);
      }
      else {
        $element['#description'] .= t('Enter one value per line.');
      }
    }

    // Set validation.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateSchemaDotOrgSettings']);
    return $element;
  }

  /**
   * Form element validation handler for #type 'schemadotorg_settings'.
   */
  public static function validateSchemaDotOrgSettings(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    try {
      $settings = static::convertElementValueToSettings($element, $form_state);
      $form_state->setValueForElement($element, $settings);
    }
    catch (\Exception $exception) {
      $form_state->setError($element, $exception->getMessage());
    }
  }

  /**
   * Get the array item format for Schema.org settings form element.
   *
   * @param array $element
   *   The Schema.org settings form element.
   *
   * @return string
   *   The array item format for the Schema.org settings form element.
   */
  protected static function getSettingsFormat(array $element): string {
    $formats = [
      static::INDEXED => '',
      static::INDEXED_GROUPED => 'name|item_1,item_2,item_3',
      static::INDEXED_GROUPED_NAMED => 'name|label|item_1,item_2,item_3',
      static::ASSOCIATIVE => 'key|value',
      static::ASSOCIATIVE_GROUPED => 'name|key_1:value_1,key_2:value_2,key_3:value_3',
      static::ASSOCIATIVE_GROUPED_NAMED => 'name|label|key_1:value_1,key_2:value_2,key_3:value_3',
      static::LINKS => 'url|title',
      static::LINKS_GROUPED => 'group or url|title',
    ];
    return $element['#settings_format'] ?: $formats[$element['#settings_type']];
  }

  /**
   * Converted Schema.org settings to an element's default value string.
   *
   * @param array $element
   *   The Schema.org settings form element.
   *
   * @return array|mixed|string
   *   An element's default value string.
   */
  protected static function convertSettingsToElementDefaultValue(array $element): mixed {
    $settings = $element['#default_value'] ?? NULL;
    if (!is_array($settings)) {
      return $settings;
    }

    switch ($element['#settings_type']) {
      case static::INDEXED:
        return static::convertIndexedArrayToString($settings);

      case static::INDEXED_GROUPED:
        $lines = [];
        foreach ($settings as $name => $values) {
          $lines[] = $name . '|' . static::convertIndexedArrayToString($values, ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::INDEXED_GROUPED_NAMED:
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $lines = [];
        foreach ($settings as $name => $group) {
          $label = $group[$group_name] ?? $name;
          $array = $group[$array_name] ?? [];
          $lines[] = $name . '|' . $label . '|' . static::convertIndexedArrayToString($array, ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::ASSOCIATIVE:
        return static::convertAssociativeArrayToString($settings);

      case static::ASSOCIATIVE_GROUPED:
        $lines = [];
        foreach ($settings as $name => $array) {
          $lines[] = $name . '|' . static::convertAssociativeArrayToString($array, ':', ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::ASSOCIATIVE_GROUPED_NAMED:
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $lines = [];
        foreach ($settings as $name => $group) {
          $label = $group[$group_name] ?? $name;
          $array = $group[$array_name] ?? [];
          $lines[] = $name . '|' . $label . '|' . static::convertAssociativeArrayToString($array, ':', ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::LINKS:
        $lines = [];
        foreach ($settings as $link) {
          $lines[] = $link['uri'] . '|' . $link['title'];
        }
        return implode("\n", $lines);

      case static::LINKS_GROUPED:
        $lines = [];
        foreach ($settings as $group => $links) {
          $lines[] = $group;
          foreach ($links as $link) {
            $lines[] = $link['uri'] . '|' . $link['title'];
          }
        }
        return implode("\n", $lines);
    }

    return $settings;
  }

  /**
   * Convert a Schema.org settings form element's value to an array of settings.
   *
   * @param array $element
   *   The Schema.org settings form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of setting.
   *
   * @throws \Exception
   *   Throw an exception when there is a validation error.
   */
  protected static function convertElementValueToSettings(array $element, FormStateInterface $form_state): array {
    $value = $element['#value'];
    switch ($element['#settings_type']) {
      case static::INDEXED:
        return static::convertStringToIndexedArray($value);

      case static::INDEXED_GROUPED:
        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $group) {
          if (substr_count($group, '|') !== 1) {
            $message = (string) t('The @value is not valid.', ['@value' => $group]);
            throw new \Exception($message);
          }

          [$name, $items] = explode('|', $group);
          $name = trim($name);
          $settings[$name] = static::convertStringToIndexedArray($items, ',');
        }
        return $settings;

      case static::INDEXED_GROUPED_NAMED;
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $group) {
          if (substr_count($group, '|') !== 2) {
            $message = (string) t('The @value is not valid.', ['@value' => $group]);
            throw new \Exception($message);
          }

          [$name, $label, $items] = explode('|', $group);

          $name = trim($name);
          $settings[$name] = [
            $group_name => $label ?: $name,
            $array_name => static::convertStringToIndexedArray($items, ','),
          ];
        }
        return $settings;

      case static::ASSOCIATIVE:
        return static::convertStringToAssociativeArray($value);

      case static::ASSOCIATIVE_GROUPED:
        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $item) {
          if (substr_count($item, '|') !== 1) {
            $message = (string) t('The @value is not valid.', ['@value' => $item]);
            throw new \Exception($message);
          }

          [$name, $items] = explode('|', $item);

          $name = trim($name);
          $settings[$name] = static::convertStringToAssociativeArray($items, ':', ',');
        }
        return $settings;

      case static::ASSOCIATIVE_GROUPED_NAMED;
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $group) {
          if (substr_count($group, '|') !== 2) {
            $message = (string) t('The @value is not valid.', ['@value' => $group]);
            throw new \Exception($message);
          }

          [$name, $label, $items] = explode('|', $group);

          $name = trim($name);
          $settings[$name] = [
            $group_name => $label ?: $name,
            $array_name => static::convertStringToAssociativeArray($items, ':', ','),
          ];
        }
        return $settings;

      case static::LINKS:
        $settings = [];
        $array = static::convertStringToAssociativeArray($value);
        foreach ($array as $key => $value) {
          $settings[] = [
            'uri' => $key,
            'title' => $value ?? static::getLinkTitle($value),
          ];
        }
        return $settings;

      case static::LINKS_GROUPED:
        $settings = [];
        $group = NULL;
        $array = static::convertStringToIndexedArray($value);
        foreach ($array as $item) {
          if (str_starts_with($item, 'http')) {
            if ($group === NULL) {
              $message = (string) t('The @value is not valid.', ['@value' => $item]);
              throw new \Exception($message);
            }
            $items = preg_split('/\s*\|\s*/', $item);
            $uri = $items[0];
            $title = $items[1] ?? static::getLinkTitle($uri);
            $settings[$group][] = ['uri' => $uri, 'title' => $title];
          }
          else {
            $group = $item;
            $settings[$group] = [];
          }
        }
        return $settings;
    }

    return [];
  }

  /**
   * Convert as indexed array to a string.
   *
   * @param array $array
   *   An indexed array.
   * @param string $delimiter
   *   The item delimiter.
   *
   * @return string
   *   The indexed array converted to a string.
   */
  protected static function convertIndexedArrayToString(array $array, string $delimiter = "\n"): string {
    return ($array) ? implode($delimiter, $array) : '';
  }

  /**
   * Convert an associative array to a string.
   *
   * @param array $array
   *   An associative array.
   * @param string $assoc_delimiter
   *   The associative delimiter.
   * @param string $item_delimiter
   *   The item delimiter.
   *
   * @return string
   *   The associative array converted to a string.
   */
  protected static function convertAssociativeArrayToString(array $array, string $assoc_delimiter = '|', string $item_delimiter = "\n"): string {
    $lines = [];
    foreach ($array as $key => $value) {
      $lines[] = ($value !== NULL) ? "$key$assoc_delimiter$value" : $key;
    }
    return implode($item_delimiter, $lines);
  }

  /**
   * Convert string to an indexed array.
   *
   * @param string $string
   *   The raw string to convert into an indexed array.
   * @param string $delimiter
   *   The item delimiter.
   *
   * @return array
   *   An indexed array.
   */
  protected static function convertStringToIndexedArray(string $string, string $delimiter = "\n"): array {
    $list = explode($delimiter, $string);
    $list = array_map('trim', $list);
    return array_filter($list, 'strlen');
  }

  /**
   * Convert string to an associative array.
   *
   * @param string $string
   *   The raw string to convert into an associative array.
   * @param string $assoc_delimiter
   *   The association delimiter.
   * @param string $item_delimiter
   *   The item delimiter.
   *
   * @return array
   *   An associative array.
   */
  protected static function convertStringToAssociativeArray(string $string, string $assoc_delimiter = '|', string $item_delimiter = "\n"): array {
    $items = static::convertStringToIndexedArray($string, $item_delimiter);
    $array = [];
    foreach ($items as $item) {
      $parts = explode($assoc_delimiter, $item);
      $key = $parts[0];
      $value = $parts[1] ?? NULL;
      $array[trim($key)] = (!is_null($value)) ? trim($value) : $value;
    }
    return $array;
  }

  /**
   * Get a remote URI's page title.
   *
   * @param string $uri
   *   The remote URI.
   *
   * @return string
   *   The remote URI's page title.
   */
  protected static function getLinkTitle(string $uri): string {
    $contents = file_get_contents($uri);
    $dom = new \DOMDocument();
    @$dom->loadHTML($contents);
    $title_node = $dom->getElementsByTagName('title');
    $title = $title_node->item(0)->nodeValue;
    [$title] = preg_split('/\s*\|\s*/', $title);
    return $title;
  }

}
