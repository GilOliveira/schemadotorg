<?php

namespace Drupal\schemadotorg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Defines the Schema.org mapping entity type.
 *
 * @ConfigEntityType(
 *   id = "schemadotorg_mapping",
 *   label = @Translation("Schema.org mapping"),
 *   label_collection = @Translation("Schema.org mappings"),
 *   label_singular = @Translation("Schema.org mapping"),
 *   label_plural = @Translation("Schema.org mappings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Schema.org mapping",
 *     plural = "@count Schema.org mappings",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\schemadotorg\SchemaDotOrgMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingForm",
 *       "edit" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "schemadotorg_mapping",
 *   admin_permission = "administer schemadotorg",
 *   links = {
 *     "collection" = "/admin/structure/schemadotorg-mapping",
 *     "add-form" = "/admin/structure/schemadotorg-mapping/add",
 *     "edit-form" = "/admin/structure/schemadotorg-mapping/{schemadotorg_mapping}",
 *     "delete-form" = "/admin/structure/schemadotorg-mapping/{schemadotorg_mapping}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "targetEntityType",
 *     "bundle",
 *     "type",
 *     "properties",
 *   }
 * )
 *
 * @see \Drupal\Core\Entity\Entity\EntityViewDisplay
 */
class SchemaDotOrgMapping extends ConfigEntityBase implements SchemaDotOrgMappingInterface {

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
   * Bundle to be displayed.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Schema.org type.
   *
   * @var string
   */
  protected $type;

  /**
   * List of property mapping, keyed by field name.
   *
   * @var array
   */
  protected $properties = [];

  // @see \Drupal\Core\Entity\EntityDisplayBase::__construct

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle($bundle) {
    $this->set('bundle', $bundle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->targetEntityType . '.' . $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchemaType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaProperties() {
    return $this->properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaProperty($name) {
    return $this->properties[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchemaProperty($name, array $mapping = []) {
    $this->properties[$name] = $mapping;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSchemaProperty($name) {
    unset($this->properties[$name]);
    return $this;
  }

  // @see \Drupal\Core\Entity\EntityDisplayBase::calculateDependencies
  // @see \Drupal\Core\Entity\EntityDisplayBase::onDependencyRemoval
  // @see \Drupal\Core\Entity\EntityDisplayBase::getPluginRemovedDependencies
}
