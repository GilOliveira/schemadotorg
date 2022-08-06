<?php

namespace Drupal\schemadotorg;

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
   * Set entity display field groups for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $properties
   *   The Schema.org properties to be added to field groups.
   */
  public function setFieldGroups($entity_type_id, $bundle, $schema_type, array $properties);

}
