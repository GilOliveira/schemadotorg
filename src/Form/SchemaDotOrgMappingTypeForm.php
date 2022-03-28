<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schema.org mapping type form.
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $entity
 */
class SchemaDotOrgMappingTypeForm extends EntityForm {


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $entity */
    $entity = $this->getEntity();

    if ($entity->isNew()) {
      $form['targetEntityType'] = [
        '#type' => 'select',
        '#title' => $this->t('Target entity type'),
        '#options' => $this->getTargetEntityTypeOptions(),
        '#required' => TRUE,
      ];
    }
    else {
      $form['target_entity_type_item'] = [
        '#type' => 'item',
        '#title' => $this->t('Target entity type'),
        '#markup' => $entity->label(),
      ];
    }
    $form['default_schema_types'] = [
      '#type' => 'textarea',
      '#title' => 'Default Schema.org types',
      '#description' => $this->t('Enter one value per line, in the format entity_type|schema_type.'),
      '#default_value' => $this->keyValuesString($entity->get('default_schema_types')),
      '#element_validate' => [[static::class, 'validateKeyValues']],
    ];
    $form['default_schema_properties'] = [
      '#type' => 'textarea',
      '#title' => 'Default Schema.org properties',
      '#description' => $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($entity->get('default_schema_properties')),
      '#element_validate' => [[static::class, 'validateList']],
    ];
    $form['default_base_fields'] = [
      '#type' => 'textarea',
      '#title' => 'Default base field mappings',
      '#description' => $this->t('Enter one value per line, in the format base_field_name|property_name.')
      . '<br/>' . $this->t('The property_name value be left blank if you want the base field available but not mapped to a Schema.org property.'),
      '#default_value' => $this->keyValuesString($entity->get('default_base_fields')),
      '#element_validate' => [[static::class, 'validateKeyValues']],
    ];
    $form['default_unlimited_fields'] = [
      '#type' => 'textarea',
      '#title' => 'Default unlimited Schema.org properties',
      '#description' => $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($entity->get('default_unlimited_fields')),
      '#element_validate' => [[static::class, 'validateList']],
    ];
    $form['recommended_schema_types'] = [
      '#type' => 'textarea',
      '#title' => 'Recommended Schema.org types',
      '#description' => $this->t('Enter one value per line, in the format group_name|group_label|SchemaType01,SchemaType01,SchemaType01.'),
      '#default_value' => $this->groupedListString($entity->get('recommended_schema_types')),
      '#element_validate' => [[static::class, 'validateGroupedList']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created %label mapping type.', $message_args)
      : $this->t('Updated %label mapping type.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /* ************************************************************************ */
  // Options.
  /* ************************************************************************ */

  /**
   * Get available target content entity type options.
   *
   * @return array
   *   Available target content entity type options.
   */
  protected function getTargetEntityTypeOptions() {
    $definitions = $this->entityTypeManager->getDefinitions();
    $options = [];
    foreach ($definitions as $definition) {
      // @todo Skip existing entity types.
      if ($definition instanceof ContentEntityTypeInterface) {
        $options[$definition->id()] = $definition->getLabel();
      }
    }
    return $options;
  }

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
      [$key, $value] = preg_split('/\s*\|\s*/', $text);
      $values[$key] = $value ?? NULL;
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
  protected function keyValuesString($values) {
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
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
      [$name, $label, $types] = explode('|', $text);
      $name = trim($name);
      $values[$name] = [
        'label' => $label ?? $name,
        'types' => $types ? preg_split('/\s*,\s*/', $types) : [],
      ];
    }
    return $values;
  }

  /**
   * Generates a string representation of an array of grouped list .
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
  protected function groupedListString($values) {
    $lines = [];
    foreach ($values as $name => $group) {
      $label = $group['label'] ?? $name;
      $types = $group['types'] ?? [];
      $lines[] = $name . '|' . $label . '|' . ($types ? implode(',', $types) : '');
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
    $list = array_filter($list, 'strlen');
    return $list;
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
  protected function listString($values) {
    return implode("\n", $values);
  }

}
