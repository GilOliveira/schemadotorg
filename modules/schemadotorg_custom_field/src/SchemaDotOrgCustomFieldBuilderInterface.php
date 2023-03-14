<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_custom_field;

use Drupal\Core\Form\FormStateInterface;

/**
 * Schema.org Custom Field builder interface.
 */
interface SchemaDotOrgCustomFieldBuilderInterface {

  /**
   * Alter forms for field widgets provided by other modules.
   *
   * Appends units to custom field form widgets.
   *
   * @param array $element
   *   The field widget form element as constructed by
   *   \Drupal\Core\Field\WidgetBaseInterface::form().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $context
   *   An associative array containing the following key-value pairs:
   *   - form: The form structure to which widgets are being attached. This may be
   *     a full form structure, or a sub-element of a larger form.
   *   - widget: The widget plugin instance.
   *   - items: The field values, as a
   *     \Drupal\Core\Field\FieldItemListInterface object.
   *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
   *   - default: A boolean indicating whether the form is being shown as a dummy
   *     form to set default values.
   */
  public function fieldWidgetFormAlter(array &$element, FormStateInterface $form_state, array $context): void;

  /**
   * Preprocess variables for customfield.html.twig.
   *
   * Appends units to custom field values.
   *
   * @param array $variables
   *   Variables for customfield.html.twig.
   */
  public function preprocessCustomField(array &$variables): void;

}
