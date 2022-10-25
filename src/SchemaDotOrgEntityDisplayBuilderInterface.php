<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Schema.org entity display builder interface.
 */
interface SchemaDotOrgEntityDisplayBuilderInterface {

  /**
   * Gets default field weights.
   *
   * @return array
   *   An array containing default field weights.
   */
  public function getDefaultFieldWeights(): array;

  /**
   * Set entity displays for a field.
   *
   * @param array $field_values
   *   Field config values.
   * @param string|null $widget_id
   *   The plugin ID of the widget.
   * @param array $widget_settings
   *   An array of widget settings.
   * @param string|null $formatter_id
   *   The plugin ID of the formatter.
   * @param array $formatter_settings
   *   An array of formatter settings.
   */
  public function setFieldDisplays(array $field_values, ?string $widget_id, array $widget_settings, ?string $formatter_id, array $formatter_settings): void;

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
  public function setFieldWeights(string $entity_type_id, string $bundle, array $properties): void;

  /**
   * Set the default component weights for a Schema.org mapping entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   */
  public function setComponentWeights(string $entity_type_id, string $bundle): void;

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
  public function isNodeTeaserDisplay(EntityDisplayInterface $display): bool;

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
  public function getFormModes(string $entity_type_id, string $bundle): array;

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
  public function getViewModes(string $entity_type_id, string $bundle): array;

}
