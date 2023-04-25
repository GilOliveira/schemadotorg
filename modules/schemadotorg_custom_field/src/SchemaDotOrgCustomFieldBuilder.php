<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_custom_field;

use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org Custom Field builder.
 */
class SchemaDotOrgCustomFieldBuilder implements SchemaDotOrgCustomFieldBuilderInterface {

  /**
   * Constructs a SchemaDotOrgCustomFieldBuilder object.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldManagerInterface $schemaCustomFieldManager
   *   The Schema.org Custom Field manager.
   */
  public function __construct(
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgCustomFieldManagerInterface $schemaCustomFieldManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function preprocessCustomField(array &$variables): void {
    foreach ($variables['items'] as $index => &$item) {
      if ($item['value'] === '') {
        unset($variables['items'][$index]);
        continue;
      }

      // Append property unit to numeric value.
      $property = $this->schemaNames->snakeCaseToCamelCase($item['name']);
      $unit = $this->schemaTypeManager->getPropertyUnit($property, $item['value']);
      if ($unit) {
        $item['value'] .= ' ' . $unit;
      }
    }
  }

}
