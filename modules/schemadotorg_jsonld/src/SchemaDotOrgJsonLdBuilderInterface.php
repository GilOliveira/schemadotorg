<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org JSON-LD builder interface.
 */
interface SchemaDotOrgJsonLdBuilderInterface {

  /**
   * Build JSON-LD for a route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface|null $route_match
   *   A route match.
   *
   * @return array|bool
   *   The JSON-LD for a route.
   */
  public function build(?RouteMatchInterface $route_match = NULL): array|bool;

  /**
   * Build JSON-LD for an entity that is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param array $options
   *   The entity build options.
   *
   * @return array|bool
   *   The JSON-LD for an entity that is mapped to a Schema.org type
   *   or FALSE if the entity is not mapped to a Schema.org type.
   */
  public function buildEntity(EntityInterface $entity, array $options = []): array|bool;

}
