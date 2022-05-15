<?php

namespace Drupal\schemadotorg\Element;

use Drupal\Core\Render\Element\Textarea;
use Drupal\Core\Form\FormStateInterface;

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
      '#attributes' => ['wrap' => 'off'],
      '#process' => [
        [$class, 'processSchemaDotOrgSettings'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#settings_type' => static::INDEXED,
      '#group_name' => 'name',
      '#array_name' => 'items',
      '#settings_format' => '',
      '#settings_description' => TRUE,
      '#description' => '',
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
    // Append description with or without format.
    if ($element['#settings_description']) {
      $formats = [
        static::INDEXED => '',
        static::INDEXED_GROUPED => 'name|item_1,item_2,item_3',
        static::INDEXED_GROUPED_NAMED => 'name|label|item_1,item_2,item_3',
        static::ASSOCIATIVE => 'key|value',
        static::ASSOCIATIVE_GROUPED => 'name|key_1:value_1,key_2:value_2,key_3:value_3',
        static::ASSOCIATIVE_GROUPED_NAMED => 'name|label|key_1:value_1,key_2:value_2,key_3:value_3',
        static::LINKS => 'url|title',
        static::LINKS_GROUPED => 'name or url|title',
      ];
      $element['#description'] .= (!empty($element['#description'])) ? '<br/><br/>' : '';
      $format = $element['#settings_format'] ?: $formats[$element['#settings_type']];
      if ($format) {
        $t_args = ['@format' => $format];
        $element['#description'] .= t('Enter one value per line, in the format <code>@format</code>.', $t_args);
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
  public static function validateSchemaDotOrgSettings(&$element, FormStateInterface $form_state, &$complete_form) {
    $settings = static::convertElementValueToSettings($element, $form_state);
    if (!is_array($settings)) {
      $form_state->setError($element, t('%title: invalid input.', ['%title' => $element['#title']]));
    }
    else {
      $form_state->setValueForElement($element, $settings);
    }
  }

  /**
   * Converted Schema.org settings to an element's default value string.
   *
   * @param array $element
   *   A Schema.org settings form element.
   *
   * @return array|mixed|string
   *   An element's default value string.
   */
  protected static function convertSettingsToElementDefaultValue(array $element) {
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
   *   A Schema.org settings form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|false
   *   An array of setting or FALSE if the element's value can be converted to
   *   settings.
   */
  protected static function convertElementValueToSettings(array $element, FormStateInterface $form_state) {
    $value = $element['#value'];
    switch ($element['#settings_type']) {
      case static::INDEXED:
        return static::convertStringToIndexedArray($value);

      case static::INDEXED_GROUPED:
        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $group) {
          if (substr_count($group, '|') !== 1) {
            return FALSE;
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
            return FALSE;
          }

          [$name, $label, $items] = explode('|', $group);

          $name = trim($name);
          $settings[$name] = [
            $group_name => $label ?? $name,
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
            return FALSE;
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
            return FALSE;
          }

          [$name, $label, $items] = explode('|', $group);

          $name = trim($name);
          $settings[$name] = [
            $group_name => $label ?? $name,
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
          if (strpos($item, 'http') === 0) {
            if ($group === NULL) {
              return FALSE;
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
  protected static function convertIndexedArrayToString(array $array, $delimiter = "\n") {
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
  protected static function convertAssociativeArrayToString(array $array, $assoc_delimiter = '|', $item_delimiter = "\n") {
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
  protected static function convertStringToIndexedArray($string, $delimiter = "\n") {
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
  protected static function convertStringToAssociativeArray($string, $assoc_delimiter = '|', $item_delimiter = "\n") {
    $items = static::convertStringToIndexedArray($string, $item_delimiter);
    $array = [];
    foreach ($items as $item) {
      $value = NULL;
      [$key, $value] = explode($assoc_delimiter, $item);
      $array[trim($key)] = (!is_null($value)) ? trim($value) : $value;
    }
    return $array;
  }

  /**
   * Get a remote URI's page title.
   *
   * @param string $uri
   *   A remote URI.
   *
   * @return string
   *   A remote URI's page title.
   */
  protected static function getLinkTitle($uri) {
    $contents = file_get_contents($uri);
    $dom = new \DOMDocument();
    @$dom->loadHTML($contents);
    $title_node = $dom->getElementsByTagName('title');
    $title = $title_node->item(0)->nodeValue;
    [$title] = preg_split('/\s*\|\s*/', $title);
    return $title;
  }

}
