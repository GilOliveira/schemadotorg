<?php

namespace Drupal\schemadotorg_jsonapi;

use Drupal\field\FieldConfigInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org JSON:API manager interface.
 */
interface SchemaDotOrgJsonApiManagerInterface {

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
   * Get resource type's entity reference fields as an array of includes.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type.
   *
   * @return array
   *   An array of entity reference field public names to be used as includes.
   */
  public function getResourceIncludes(ResourceType $resource_type);

  /**
   * Insert Schema.org mapping JSON:API resource config.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function insertMappingResourceConfig(SchemaDotOrgMappingInterface $mapping);

  /**
   * Update Schema.org mapping JSON:API resource config.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
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
