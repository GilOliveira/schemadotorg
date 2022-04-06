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
  // Links.
  /* ************************************************************************ */

  /**
   * Element validate callback for links.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateLinks(array $element, FormStateInterface $form_state) {
    $values = static::extractLinks($element['#value']);
    $form_state->setValueForElement($element, $values);
  }

  /**
   * Extracts the links array from the links element.
   *
   * @param string $string
   *   The raw string to extract links from.
   *
   * @return array
   *   The array of extracted links.
   */
  protected static function extractLinks($string) {
    $values = [];
    $list = static::extractList($string);
    foreach ($list as $text) {
      $items = preg_split('/\s*\|\s*/', $text);
      $uri = $items[0];
      $title = $items[1] ?? static::getLinkTitle($uri);
      $values[] = ['uri' => $uri, 'title' => $title];
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of links.
   *
   * @param array $values
   *   An array of links.
   *
   * @return string
   *   The string representation of links pairs:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "uri|title" or "uri".
   */
  protected function linksString(array $values) {
    $lines = [];
    foreach ($values as $link) {
      $lines[] = $link['uri'] . '|' . $link['title'];
    }
    return implode("\n", $lines);
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

  /* ************************************************************************ */
  // Grouped links.
  /* ************************************************************************ */

  /**
   * Element validate callback for grouped links.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateGroupedLinks(array $element, FormStateInterface $form_state) {
    $values = static::extractGroupedLinks($element['#value']);
    if (!is_array($values)) {
      $form_state->setError($element, t('%title: invalid input.', ['%title' => $element['#title']]));
      return;
    }

    $form_state->setValueForElement($element, $values);
  }

  /**
   * Extracts the grouped list array from the grouped links element.
   *
   * @param string $string
   *   The raw string to extract grouped links from.
   *
   * @return array
   *   The array of extracted grouped links.
   */
  protected static function extractGroupedLinks($string) {
    $values = [];
    $list = static::extractList($string);
    $group = NULL;
    foreach ($list as $text) {
      if (strpos($text, 'http') === 0) {
        if ($group === NULL) {
          return FALSE;
        }
        $items = preg_split('/\s*\|\s*/', $text);
        $uri = $items[0];
        $title = $items[1] ?? static::getLinkTitle($uri);
        $values[$group][] = ['uri' => $uri, 'title' => $title];
      }
      else {
        $group = $text;
        $values[$group] = [];
      }
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of grouped links.
   *
   * @param array $values
   *   An array containing grouped links.
   *
   * @return string
   *   The string representation of a grouped links:
   *    - Group and links separated by a carriage return.
   */
  protected function groupedLinksString(array $values) {
    $lines = [];
    foreach ($values as $name => $links) {
      $lines[] = $name;
      foreach ($links as $link) {
        $lines[] = $link['uri'] . '|' . $link['title'];
      }
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
