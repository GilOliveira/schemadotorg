<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org JSON-LD manager interface.
 */
interface SchemaDotOrgJsonLdManagerInterface {

  /**
   * Get an entity's canonical route match.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return \Drupal\Core\Routing\RouteMatch|null
   *   An entity's canonical route match.
   */
  public function getEntityRouteMatch(EntityInterface $entity, $rel = 'canonical');

  /**
   * Returns the entity of the current route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface|null $route_match
   *   A route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or NULL if this is not an entity route.
   *
   * @see metatag_get_route_entity()
   */
  public function getRouteMatchEntity(RouteMatchInterface $route_match = NULL);

  /**
   * Sort Schema.org properties in specified order and then alphabetically.
   *
   * @param array $properties
   *   An associative array of Schema.org properties.
   *
   * @return array
   *   The Schema.org propertiesin specified order and then alphabetically.
   */
  public function sortProperties(array $properties);

  /**
   * Get a Schema.org property's value for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return array|mixed|null
   *   A Schema.org property's value for a field item.
   */
  public function getSchemaPropertyValue(FieldItemInterface $item);

  /**
   * Get a Schema.org type property's value converted to the default Schema.org type.
   *
   * @param string $type
   *   The Schema.org type.
   * @param string $property
   *   The Schema.org property.
   * @param string|mixed $value
   *   The Schema.org property's value.
   *
   * @return array|string
   *   The Schema.org property's value converted to the default Schema.org type.
   */
  public function getSchemaPropertyValueDefaultType($type, $property, $value);

  /**
   * Get Schema.org identifiers for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   An array of identifiers containing Schema.org PropertyValue types.
   */
  public function getSchemaIdentifiers(EntityInterface $entity);

}
