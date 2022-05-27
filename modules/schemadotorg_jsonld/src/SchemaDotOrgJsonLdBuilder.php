<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org JSON-LD builder.
 */
class SchemaDotOrgJsonLdBuilder implements SchemaDotOrgJsonLdBuilderInterface {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org JSON-LD manager.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface
   */
  protected $schemaJsonIdManager;

  /**
   * Constructs a SchemaDotOrgJsonLdBuilder object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager
   *   The Schema.org JSON-LD manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager
  ) {
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaJsonIdManager = $schema_jsonld_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match = NULL) {
    $route_match = $route_match ?: $this->routeMatch;

    $data = [];

    // Add custom data.
    $custom_data = $this->buildCustom($route_match) ?: [];
    $data += $custom_data;

    // Add entity data.
    $entity = $this->schemaJsonIdManager->getRouteEntity($route_match);
    if ($entity) {
      $entity_data = $this->buildEntity($entity);
      if ($entity_data) {
        $entity_data = ['@context' => 'https://schema.org'] + $entity_data;
        $data['schemadotorg_jsonld'] = [$entity_data];
      }
    }

    // Alter Schema.org JSON-LD data for the current rouut.
    $this->moduleHandler->alter(
      'schemadotorg_jsonld',
      $data,
      $route_match
    );

    // Return FALSE if the data is empty.
    if (empty($data)) {
      return FALSE;
    }

    return (count($data) === 1) ? reset($data) : array_values($data);
  }

  /**
   * Builds custom JSON-LD data for the current route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   An array of custom JSON-LD data.
   */
  protected function buildCustom(RouteMatchInterface $route_match) {
    $data = [];

    $hook = 'schemadotorg_jsonld';
    $args = [$route_match];
    $implementations = $this->moduleHandler->getImplementations($hook);
    foreach ($implementations as $module) {
      $module_data = $this->moduleHandler->invoke($module, $hook, $args);
      // @todo Validate JSON-LD to ensure the @type property is defined.
      // @todo Determine how to handle multiple definitions values.
      if ($module_data) {
        $data[$module] = $module_data;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(EntityInterface $entity) {
    $data = $this->buildMappedEntity($entity);

    // Define custom data which can still have identifiers.
    if (!$data) {
      $identifiers = $this->schemaJsonIdManager->getSchemaIdentifiers($entity);
      $data = $identifiers ? ['identifier' => $identifiers] : [];
    }

    // Alter Schema.org JSON-LD entity data.
    $this->moduleHandler->alter(
      'schemadotorg_jsonld_entity',
      $data,
      $entity
    );

    // Return data if a Schema.org @type is defined.
    return (isset($data['@type']))
      ? $data
      : FALSE;
  }

  /**
   * Build JSON-LD for an entity that is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return array|bool
   *   The JSON-LD for an entity that is mapped to a Schema.org type
   *   or FALSE if the entity is not mapped to a Schema.org type.
   */
  protected function buildMappedEntity(EntityInterface $entity) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    if (!$mapping_storage->isEntityMapped($entity)) {
      return FALSE;
    }

    $schema_type_data = [];

    $mapping = $mapping_storage->loadByEntity($entity);

    $schema_properties = $mapping->getSchemaProperties();
    foreach ($schema_properties as $field_name => $schema_property) {
      // Make sure the entity has the field and the current user has
      // access to the field.
      if (!$entity->hasField($field_name) || !$entity->get($field_name)->access('view')) {
        continue;
      }

      // Make sure the user has access to the field.
      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $entity->get($field_name);

      // Get the Schema.org properties.
      $schema_property_data = [];
      foreach ($items as $item) {
        $schema_property_value = $this->getFieldItem($item);

        // Alter the Schema.org property's individual value.
        $this->moduleHandler->alter(
          'schemadotorg_jsonld_schema_property',
          $schema_property_value,
          $item
        );

        if ($schema_property_value !== NULL) {
          $schema_property_data[] = $schema_property_value;
        }
      }

      // If the cardinality is 1, return the first property data item.
      $cardinality = $items
        ->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality();
      if ($schema_property_data) {
        $schema_type_data[$schema_property] = ($cardinality === 1) ? reset($schema_property_data) : $schema_property_data;
      }
    }

    if (!$schema_type_data) {
      return FALSE;
    }

    // Add Schema.org identifiers. (Defaults to UUID)
    $identifiers = $this->schemaJsonIdManager->getSchemaIdentifiers($entity);
    if ($identifiers) {
      // Make sure exiting identifier data is an indexed array.
      if (isset($schema_type_data['identifier']) && is_array($schema_type_data['identifier'])) {
        if (!isset($schema_type_data['identifier'][0])) {
          $schema_type_data['identifier'] = [$schema_type_data['identifier']];
        }
      }
      else {
        $schema_type_data['identifier'] = [];
      }
      $schema_type_data['identifier'] = array_merge($schema_type_data['identifier'], $identifiers);
    }

    // Sort Schema.org properties in specified order and then alphabetically.
    $schema_type_data = $this->schemaJsonIdManager->sortProperties($schema_type_data);

    // Prepend the @type and @url to the returned data.
    $schema_type = $mapping->getSchemaType();
    $schema_subtype = $mapping_storage->getSubtype($entity);
    $default_data = ['@type' => $schema_subtype ?: $schema_type];
    if ($entity->hasLinkTemplate('canonical') && $entity->access('view')) {
      $default_data['@url'] = $entity->toUrl('canonical')->setAbsolute()->toString();
    }
    $schema_type_data = $default_data + $schema_type_data;

    // Alter Schema.org type's JSON-LD data.
    $this->moduleHandler->alter(
      'schemadotorg_jsonld_entity',
      $schema_type_data,
      $entity
    );

    return $schema_type_data;
  }

  /**
   * Get Schema.org property data type from field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface|null $item
   *   The field item.
   *
   * @return array|bool|mixed|null
   *   A data type.
   */
  protected function getFieldItem(FieldItemInterface $item = NULL) {
    if ($item === NULL) {
      return NULL;
    }

    // Handle entity reference relationships.
    if ($item->entity && $item->entity instanceof EntityInterface) {
      $entity_data = $this->buildMappedEntity($item->entity);
      if ($entity_data) {
        return $entity_data;
      }
    }

    // Get Schema.org property value.
    return $this->schemaJsonIdManager->getSchemaPropertyValue($item);
  }

}
