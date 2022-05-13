<?php

namespace Drupal\schemadotorg_jsonapi_extras;

use Drupal\field\FieldConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org JSON:API extras interface.
 */
interface SchemaDotOrgJsonApiExtrasManagerInterface {

  /**
   * Check installation requirements.
   *
   * @param string $phase
   *   The phase in which requirements are checked.
   *
   * @return array
   *   An associative array containing installation requirements.
   */
  public function requirements($phase);

  /**
   * Install Schema.org mapping JSON:API resource config.
   */
  public function install();

  /**
   * Insert Schema.org mapping JSON:API resource config.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   A Schema.org mapping.
   */
  public function insertMappingResourceConfig(SchemaDotOrgMappingInterface $mapping);

  /**
   * Update Schema.org mapping JSON:API resource config.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   A Schema.org mapping.
   */
  public function updateMappingResourceConfig(SchemaDotOrgMappingInterface $mapping);

  /**
   * Insert field into JSON:API resource config.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field.
   */
  public function insertFieldConfigResource(FieldConfigInterface $field);

}