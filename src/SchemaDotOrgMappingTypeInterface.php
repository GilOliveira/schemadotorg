<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Provides an interface defining a Schema.org mapping type entity.
 *
 * @see \Drupal\Core\Entity\Display\EntityDisplayInterface
 */
interface SchemaDotOrgMappingTypeInterface extends ConfigEntityInterface {

  /**
   * Gets default bundle for a Schema.org type.
   *
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   The default bundles for a Schema.org type.
   */
  public function getDefaultSchemaTypeBundles($type);

  /**
   * Gets default Schema.org type for a bundle.
   *
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string|null
   *   The default Schema.org type for a bundle.
   */
  public function getDefaultSchemaType($bundle);

  /**
   * Gets default Schema.org type's default properties.
   *
   * @param string $schema_type
   *   A Schema.org type.
   *
   * @return array
   *   A Schema.org type's default properties.
   */
  public function getDefaultSchemaTypeProperties($schema_type);

  /**
   * Determine if the mapping type supports multiple Schema.org type mappings.
   *
   * @return bool
   *   TRUE if the mapping type supports multiple Schema.org type mappings.
   */
  public function supportsMultiple();

  /**
   * Gets default field weights.
   *
   * @return array
   *   An array containing default field weights.
   */
  public function getDefaultFieldWeights();

  /**
   * Gets default field groups.
   *
   * @return array
   *   An associative array containing default field groups.
   */
  public function getDefaultFieldGroups();

  /**
   * Gets default field group label suffix.
   *
   * @return string
   *   The default field group label suffix.
   */
  public function getDefaultFieldGroupLabelSuffix();

  /**
   * Gets default field group format type.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   *
   * @return string
   *   The default field group format type.
   */
  public function getDefaultFieldGroupFormatType(EntityDisplayInterface $display);

  /**
   * Gets default field group format settings.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   *
   * @return array
   *   The default field group format settings.
   */
  public function getDefaultFieldGroupFormatSettings(EntityDisplayInterface $display);

  /**
   * Gets common Schema.org types.
   *
   * @return array
   *   An associative array containing common Schema.org types.
   */
  public function getRecommendedSchemaTypes();

  /**
   * Gets an entity type's base field mappings.
   *
   * @return array
   *   An entity type's base field mappings.
   */
  public function getBaseFieldMappings();

  /**
   * Gets an entity type's base fields names.
   *
   * @return array
   *   An entity type's base fields names.
   */
  public function getBaseFieldNames();

}
