<?php

namespace Drupal\schemadotorg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
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
 *     "storage" = "\Drupal\schemadotorg\SchemaDotOrgMappingStorage",
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
  public function id() {
    return $this->targetEntityType . '.' . $this->bundle;
  }

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
    return $this->isTargetEntityTypeBundle()
      ? $this->bundle
      : $this->getTargetEntityTypeId();
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
  public function getTargetEntityTypeDefinition() {
    return $this->entityTypeManager()->getDefinition($this->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeBundleId() {
    return $this->getTargetEntityTypeDefinition()->getBundleEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeBundleDefinition() {
    $bundle_entity_type = $this->getTargetEntityTypeBundleId();
    return $bundle_entity_type ? $this->entityTypeManager()->getDefinition($bundle_entity_type) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityBundleEntity() {
    if (!$this->isTargetEntityTypeBundle()) {
      return NULL;
    }

    $bundle = $this->getTargetBundle();
    $bundle_entity_type_id = $this->getTargetEntityTypeBundleId();
    $entity_storage = $this->entityTypeManager()->getStorage($bundle_entity_type_id);
    return $bundle ? $entity_storage->load($bundle) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isTargetEntityTypeBundle() {
    return (boolean) $this->getTargetEntityTypeBundleId();
  }

  /**
   * {@inheritdoc}
   */
  public function isNewTargetEntityTypeBundle() {
    return ($this->isTargetEntityTypeBundle() && !$this->getTargetEntityBundleEntity());
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
    $this->type = $type;
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

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $target_entity_type = $this->entityTypeManager()->getDefinition($this->targetEntityType);

    // Create dependency on the bundle.
    $bundle_config_dependency = $target_entity_type->getBundleConfigDependency($this->bundle);
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    // If field.module is enabled, add dependencies on 'field_config' entities
    // for both displayed and hidden fields. We intentionally leave out base
    // field overrides, since the field still exists without them.
    if (\Drupal::moduleHandler()->moduleExists('field')) {
      $properties = $this->properties;
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->targetEntityType, $this->bundle);
      foreach (array_intersect_key($field_definitions, $properties) as $field_name => $field_definition) {
        if ($field_definition instanceof ConfigEntityInterface && $field_definition->getEntityTypeId() == 'field_config') {
          $this->addDependency('config', $field_definition->getConfigDependencyName());
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    foreach ($dependencies['config'] as $entity) {
      if ($entity->getEntityTypeId() == 'field_config') {
        // Remove properties for fields that are being deleted.
        $this->removeSchemaProperty($entity->getName());
        $changed = TRUE;
      }
    }
    return $changed;
  }

  /**
   * Returns the plugin dependencies being removed.
   *
   * The function recursively computes the intersection between all plugin
   * dependencies and all removed dependencies.
   *
   * Note: The two arguments do not have the same structure.
   *
   * @param array[] $plugin_dependencies
   *   A list of dependencies having the same structure as the return value of
   *   ConfigEntityInterface::calculateDependencies().
   * @param array[] $removed_dependencies
   *   A list of dependencies having the same structure as the input argument of
   *   ConfigEntityInterface::onDependencyRemoval().
   *
   * @return array
   *   A recursively computed intersection.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::onDependencyRemoval()
   */
  protected function getPluginRemovedDependencies(array $plugin_dependencies, array $removed_dependencies) {
    $intersect = [];
    foreach ($plugin_dependencies as $type => $dependencies) {
      if ($removed_dependencies[$type]) {
        // Config and content entities have the dependency names as keys while
        // module and theme dependencies are indexed arrays of dependency names.
        // @see \Drupal\Core\Config\ConfigManager::callOnDependencyRemoval()
        if (in_array($type, ['config', 'content'])) {
          $removed = array_intersect_key($removed_dependencies[$type], array_flip($dependencies));
        }
        else {
          $removed = array_values(array_intersect($removed_dependencies[$type], $dependencies));
        }
        if ($removed) {
          $intersect[$type] = $removed;
        }
      }
    }
    return $intersect;
  }

}
