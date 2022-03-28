<?php

namespace Drupal\schemadotorg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface;

/**
 * Defines the Schema.org mapping type entity type.
 *
 * @ConfigEntityType(
 *   id = "schemadotorg_mapping_type",
 *   label = @Translation("Schema.org mapping type"),
 *   label_collection = @Translation("Schema.org mapping types"),
 *   label_singular = @Translation("Schema.org mapping type"),
 *   label_plural = @Translation("Schema.org mapping types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Schema.org mapping type",
 *     plural = "@count Schema.org mapping types",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage",
 *     "list_builder" = "Drupal\schemadotorg\SchemaDotOrgMappingTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingTypeForm",
 *       "edit" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "schemadotorg_mapping_type",
 *   admin_permission = "administer schemadotorg",
 *   links = {
 *     "collection" = "/admin/structure/schemadotorg-mapping/type",
 *     "add-form" = "/admin/structure/schemadotorg-mapping/type/add",
 *     "edit-form" = "/admin/structure/schemadotorg-mapping/type/{schemadotorg_mapping_type}",
 *     "delete-form" = "/admin/structure/schemadotorg-mapping/type/{schemadotorg_mapping_type}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "targetEntityType",
 *     "default_schema_types",
 *     "default_schema_properties",
 *     "default_base_fields",
 *     "default_unlimited_fields",
 *     "recommended_schema_types",
 *   }
 * )
 *
 * @see \Drupal\Core\Entity\Entity\EntityViewDisplay
 */
class SchemaDotOrgMappingType extends ConfigEntityBase implements SchemaDotOrgMappingTypeInterface {

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  protected $id;

  /**
   * Entity type to be displayed.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * An associative array of default Schema.org types.
   *
   * @var array
   */
  protected $default_schema_types = [];

  /**
   * An array of default Schema.org properties.
   *
   * @var array
   */
  protected $default_schema_properties = [];

  /**
   * An associative array of base field mappings.
   *
   * @var array
   */
  protected $default_base_fields = [];

  /**
   * An array of recommended unlimited (a.k.a. multiple value) fields.
   *
   * @var array
   */
  protected $default_unlimited_fields = [];

  /**
   * An associative array of grouped recommended Schema.org types.
   *
   * @var array
   */
  protected $recommended_schema_types = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return \Drupal::entityTypeManager()->getDefinition($this->id())->getLabel();
  }

}
