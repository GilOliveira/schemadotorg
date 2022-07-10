<?php

namespace Drupal\schemadotorg_translation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org translate manager interface.
 */
interface SchemaDotOrgTranslationManagerInterface {

  /**
   * Enable translation for a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function enableMapping(SchemaDotOrgMappingInterface $mapping);

  /**
   * Enable entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function enableEntity(EntityInterface $entity);

  /**
   * Enable translation for a Schema.org mapping field.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field.
   */
  public function enableFieldConfig(FieldConfigInterface $field_config);

}
