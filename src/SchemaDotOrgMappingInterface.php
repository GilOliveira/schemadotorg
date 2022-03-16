<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Schema.org mapping entity type.
 *
 * @see \Drupal\Core\Entity\Display\EntityDisplayInterface
 */
interface SchemaDotOrgMappingInterface extends ConfigEntityInterface {

  /**
   * Gets the entity type for which this mapping is used. (i.e. node)
   *
   * @return string
   *   The entity type id.
   */
  public function getTargetEntityTypeId();

  /**
   * Gets the bundle to be mapped. (i.e. page)
   *
   * @return string
   *   The bundle to be mapped.
   */
  public function getTargetBundle();

  /**
   * Sets the bundle to be mapped.
   *
   * @param string $bundle
   *   The name of the bundle to be mapped.
   *
   * @return $this
   */
  public function setTargetBundle($bundle);

  /**
   * Gets the entity type definition. (i.e. node annotation)
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type definition.
   */
  public function getTargetEntityTypeDefinition();

  /**
   * Get the entity type's bundle ID. (i.e. node_type)
   *
   * @return string|null
   *   The entity type's bundle ID.
   */
  public function getTargetEntityTypeBundleId();

  /**
   * Get the entity type's bundle definition. (i.e. node_type annotation)
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   Get the entity type's bundle definition.
   */
  public function getTargetEntityTypeBundleDefinition();

  /**
   * Get the bundle entity type. (i.e. node_type:page)
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityBundleBase|null
   *   The bundle entity type.
   */
  public function getTargetEntityBundleEntity();

  /**
   * Determine if the entity type supports bundling.
   *
   * @return bool
   *   TRUE if the entity type supports bundling.
   */
  public function isTargetEntityTypeBundle();

  /**
   * Determine if a new bundle entity is being created.
   *
   * @return bool
   *   TRUE if a new bundle entity is being created.
   */
  public function isNewTargetEntityTypeBundle();

  /**
   * Gets the Schema.org type to be mapped.
   *
   * @return string
   *   TheSchema.org type to be mapped.
   */
  public function getSchemaType();

  /**
   * Sets the Schema.org type to be mapped.
   *
   * @param string $type
   *   The Schema.org type to be mapped.
   *
   * @return $this
   */
  public function setSchemaType($type);

  /**
   * Gets the mappings for all Schema.org properties.
   *
   * @return array
   *   The array of Schema.org property mappings, keyed by field name.
   */
  public function getSchemaProperties();

  /**
   * Gets the mapping set for a property.
   *
   * @param string $name
   *   The name of the property.
   *
   * @return array|null
   *   The mapping for the Schema.org property, or NULL if the
   *   Schema.org property is not mapped.
   */
  public function getSchemaPropertyMapping($name);

  /**
   * Sets the mapping for a Schema.org property.
   *
   * @param string $name
   *   The field name of the Schema.org property mapping.
   * @param array $mapping
   *   The Schema.org property mapping.
   *
   * @return $this
   */
  public function setSchemaPropertyMapping($name, array $mapping = []);

  /**
   * Removes the Schema.org property mapping.
   *
   * @param string $name
   *   The name of the Schema.org property mapping.
   *
   * @return $this
   */
  public function removeSchemaProperty($name);

}