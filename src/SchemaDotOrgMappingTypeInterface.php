<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

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
  public function getDefaultSchemaTypeBundles(string $type): array;

  /**
   * Gets default Schema.org type for a bundle.
   *
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string|null
   *   The default Schema.org type for a bundle.
   */
  public function getDefaultSchemaType(string $bundle): ?string;

  /**
   * Gets default Schema.org type's default properties.
   *
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return array
   *   The Schema.org type's default properties.
   */
  public function getDefaultSchemaTypeProperties(string $schema_type): array;

  /**
   * Determine if the mapping type supports multiple Schema.org type mappings.
   *
   * @return bool
   *   TRUE if the mapping type supports multiple Schema.org type mappings.
   */
  public function supportsMultiple(): bool;

  /**
   * Gets common Schema.org types.
   *
   * @return array
   *   An associative array containing common Schema.org types.
   */
  public function getRecommendedSchemaTypes(): array;

  /**
   * Gets an entity type's base field mappings.
   *
   * @return array
   *   An entity type's base field mappings.
   */
  public function getBaseFieldMappings(): array;

  /**
   * Gets an entity type's base fields names.
   *
   * @return array
   *   An entity type's base fields names.
   */
  public function getBaseFieldNames(): array;

}
