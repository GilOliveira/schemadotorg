<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Schema.org form trait.
 *
 * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase
 */
trait SchemaDotOrgFormTrait {

  /* ************************************************************************ */
  // Key/value.
  /* ************************************************************************ */

  /**
   * Element validate callback for key/value pairs.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateKeyValues(array $element, FormStateInterface $form_state) {
    $values = static::extractKeyValues($element['#value']);
    $form_state->setValueForElement($element, $values);
  }

  /**
   * Extracts the key/value pairs array from the key/value pairs element.
   *
   * @param string $string
   *   The raw string to extract key/value pairs from.
   *
   * @return array
   *   The array of extracted key/value pairs.
   */
  protected static function extractKeyValues($string) {
    $values = [];
    $list = static::extractList($string);
    foreach ($list as $text) {
      $items = preg_split('/\s*\|\s*/', $text);
      $values[$items[0]] = $items[1] ?? NULL;
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of key/value pairs.
   *
   * @param array $values
   *   An array of key/value pairs.
   *
   * @return string
   *   The string representation of key/value pairs:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "key|value" or "value".
   */
  protected function keyValuesString(array $values) {
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = ($value !== NULL) ? "$key|$value" : $key;
    }
    return implode("\n", $lines);
  }

  /* ************************************************************************ */
  // Grouped list.
  /* ************************************************************************ */

  /**
   * Element validate callback for grouped list.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateGroupedList(array $element, FormStateInterface $form_state) {
    $values = static::extractGroupedList($element['#value']);
    if (!is_array($values)) {
      $form_state->setError($element, t('%title: invalid input.', ['%title' => $element['#title']]));
      return;
    }

    $form_state->setValueForElement($element, $values);
  }

  /**
   * Extracts the grouped list array from the grouped list element.
   *
   * @param string $string
   *   The raw string to extract grouped list from.
   *
   * @return array
   *   The array of extracted grouped list.
   */
  protected static function extractGroupedList($string) {
    $values = [];
    $list = static::extractList($string);
    foreach ($list as $text) {
      if (substr_count($text, '|') !== 2) {
        return FALSE;
      }

      [$name, $label, $types] = explode('|', $text);

      $name = trim($name);
      $values[$name] = [
        'label' => $label ?? $name,
        'types' => $types ? preg_split('/\s*,\s*/', trim($types)) : [],
      ];
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of grouped list.
   *
   * @param array $values
   *   An array containing grouped list.
   *
   * @return string
   *   The string representation of a grouped list :
   *    - Values are separated by a carriage return.
   *    - Each value begins with the group name, followed by the group label,
   *      and followed by a comma delimited list of types
   */
  protected function groupedListString(array $values) {
    $lines = [];
    foreach ($values as $name => $group) {
      $label = $group['label'] ?? $name;
      $types = $group['types'] ?? [];
      $lines[] = $name . '|' . $label . '|' . ($types ? implode(',', $types) : '');
    }
    return implode("\n", $lines);
  }

  /* ************************************************************************ */
  // Grouped list.
  /* ************************************************************************ */

  /**
   * Element validate callback for grouped URLs.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateGroupedUrls(array $element, FormStateInterface $form_state) {
    $values = static::extractGroupedUrls($element['#value']);
    if (!is_array($values)) {
      $form_state->setError($element, t('%title: invalid input.', ['%title' => $element['#title']]));
      return;
    }

    $form_state->setValueForElement($element, $values);
  }

  /**
   * Extracts the grouped list array from the grouped URLs element.
   *
   * @param string $string
   *   The raw string to extract grouped URLs from.
   *
   * @return array
   *   The array of extracted grouped URLs.
   */
  protected static function extractGroupedUrls($string) {
    $values = [];
    $list = static::extractList($string);
    $group = NULL;
    foreach ($list as $text) {
      if (strpos($text, 'http') === 0) {
        if ($group === NULL) {
          return FALSE;
        }
        $values[$group][] = $text;
      }
      else {
        $group = $text;
        $values[$group] = [];
      }
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of grouped URLs.
   *
   * @param array $values
   *   An array containing grouped list.
   *
   * @return string
   *   The string representation of a grouped URLs:
   *    - Group and URLs separated by a carriage return.
   */
  protected function groupedUrlsString(array $values) {
    $lines = [];
    foreach ($values as $name => $urls) {
      $lines[] = $name;
      $lines = array_merge($lines, $urls);
    }
    return implode("\n", $lines);
  }

  /* ************************************************************************ */
  // Nested list.
  /* ************************************************************************ */

  /**
   * Element validate callback for nested list.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateNestedList(array $element, FormStateInterface $form_state) {
    $values = static::extractNestedList($element['#value']);
    if (!is_array($values)) {
      $form_state->setError($element, t('%title: invalid input.', ['%title' => $element['#title']]));
      return;
    }

    $form_state->setValueForElement($element, $values);
  }

  /**
   * Extracts the nested list array from the nested list element.
   *
   * @param string $string
   *   The raw string to extract nested list from.
   *
   * @return array
   *   The array of extracted nested list.
   */
  protected static function extractNestedList($string) {
    $values = [];
    $list = static::extractList($string);
    foreach ($list as $text) {
      if (substr_count($text, '|') !== 1) {
        return FALSE;
      }

      [$name, $types] = explode('|', $text);
      $name = trim($name);
      $values[$name] = preg_split('/\s*,\s*/', trim($types));
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of nested list .
   *
   * @param array $values
   *   An array containing nested list.
   *
   * @return string
   *   The string representation of a nested list :
   *    - Values are separated by a carriage return.
   *    - Each value begins with the main items,
   *      and followed by a comma delimited list of sub items.
   */
  protected function nestedListString(array $values) {
    $lines = [];
    foreach ($values as $name => $items) {
      $lines[] = $name . '|' . implode(',', $items);
    }
    return implode("\n", $lines);
  }

  /* ************************************************************************ */
  // List.
  /* ************************************************************************ */

  /**
   * Element validate callback for a list.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateList(array $element, FormStateInterface $form_state) {
    $values = static::extractList($element['#value']);
    $form_state->setValueForElement($element, $values);
  }

  /**
   * Extracts the list array from the list element.
   *
   * @param string $string
   *   The raw string to extract list from.
   *
   * @return array
   *   The array of extracted list.
   */
  protected static function extractList($string) {
    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    return array_filter($list, 'strlen');
  }

  /**
   * Generates a string representation of a list.
   *
   * @param array $values
   *   An array of list items.
   *
   * @return string
   *   The string representation of a list pairs:
   *    - List items are separated by a carriage return.
   */
  protected function listString(array $values) {
    return implode("\n", $values);
  }

}
