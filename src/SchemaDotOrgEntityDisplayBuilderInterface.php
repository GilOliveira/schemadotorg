<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Schema.org entity display builder interface.
 */
interface SchemaDotOrgEntityDisplayBuilderInterface {

  /**
   * Set entity displays for a field.
   *
   * @param array $field_values
   *   Field config values.
   * @param string $widget_id
   *   The plugin ID of the widget.
   * @param array $widget_settings
   *   An array of widget settings.
   * @param string|null $formatter_id
   *   The plugin ID of the formatter.
   * @param array $formatter_settings
   *   An array of formatter settings.
   */
  public function setFieldDisplays(array $field_values, $widget_id, array $widget_settings, $formatter_id, array $formatter_settings);

  /**
   * Set entity display field weights for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $properties
   *   The Schema.org properties to be weighted.
   */
  public function setFieldWeights($entity_type_id, $bundle, array $properties);

  /**
   * Determine if a display is node teaser view display.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   *
   * @return bool
   *   TRUE if the display is node teaser view display.
   *
   * @see node_add_body_field()
   */
  public function isNodeTeaserDisplay(EntityDisplayInterface $display);

  /**
   * Get display form modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   An array of display form modes.
   */
  public function getFormModes($entity_type_id, string $bundle);

  /**
   * Get display view modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   An array of display view modes.
   */
  public function getViewModes($entity_type_id, string $bundle);

}
