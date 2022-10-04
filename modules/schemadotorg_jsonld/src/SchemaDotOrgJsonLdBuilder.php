<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org JSON-LD builder.
 *
 * The Schema.org JSON-LD builder build and hook flow.
 * - Get custom data based on the current route match.
 * - Build mapped entity based on the current entity
 * - Load custom entity data on the current entity and related entities.
 * - Alter mapped entity data on the current entity and related entities.
 * - Alter all data based on the current route match.
 *
 * @see hook_schemadotorg_jsonld()
 * @see \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilder::buildMappedEntity
 * @see hook_schemadotorg_jsonld_schema_type_entity_load()
 * @see hook_schemadotorg_jsonld_schema_type_entity_alter()
 * @see hook_schemadotorg_jsonld_schema_type_field_alter()
 * @see hook_schemadotorg_jsonld_schema_property_alter()
 * @see hook_schemadotorg_jsonld_schema_properties_alter()
 * @see hook_schemadotorg_jsonld_alter()
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
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org JSON-LD manager.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface
   */
  protected $schemaJsonLdManager;

  /**
   * Constructs a SchemaDotOrgJsonLdBuilder object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager
   *   The Schema.org JSON-LD manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager,
    SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager
  ) {
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaTypeManager = $schema_type_manager;
    $this->schemaJsonLdManager = $schema_jsonld_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(?RouteMatchInterface $route_match = NULL): array|bool {
    $route_match = $route_match ?: $this->routeMatch;

    $data = [];

    // Add custom data based on the route match.
    // @see hook_schemadotorg_jsonld()
    $this->moduleHandler->invokeAllWith('schemadotorg_jsonld', function (callable $hook, string $module) use (&$data, $route_match): void {
      $module_data = $hook($route_match);
      if ($module_data) {
        $data[$module . '_schemadotorg_jsonld'] = $module_data;
      }
    });

    // Add entity data.
    $entity = $this->schemaJsonLdManager->getRouteMatchEntity($route_match);
    $entity_data = $this->buildEntity($entity);
    if ($entity_data) {
      $data['schemadotorg_jsonld_entity'] = $entity_data;
    }

    // Alter Schema.org JSON-LD data for the current route.
    // @see hook_schemadotorg_jsonld_alter()
    $this->moduleHandler->alter('schemadotorg_jsonld', $data, $route_match);

    // Return FALSE if the data is empty.
    if (empty($data)) {
      return FALSE;
    }

    $types = $this->getSchemaTypesFromData($data);
    return (count($types) === 1) ? reset($types) : $types;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(?EntityInterface $entity = NULL, array $options = []): array|bool {
    if (!$entity) {
      return [];
    }

    // Set default options.
    $options += [
      'identifier' => TRUE,
    ];

    $data = $this->buildMappedEntity($entity);

    // Load Schema.org JSON-LD entity data.
    // @see schemadotorg_jsonld_schema_type_entity_load()
    $this->moduleHandler->invokeAllWith(
      'schemadotorg_jsonld_schema_type_entity_load',
      function (callable $hook) use (&$data, $entity): void {
        $hook($data, $entity);
      }
    );

    // Add Schema.org identifiers. (Defaults to UUID)
    if ($options['identifier']) {
      $identifiers = $this->schemaJsonLdManager->getSchemaIdentifiers($entity);
      if ($identifiers) {
        // Make sure existing identifier data is an indexed array.
        if (isset($data['identifier']) && is_array($data['identifier'])) {
          if (!isset($data['identifier'][0])) {
            $data['identifier'] = [$data['identifier']];
          }
        }
        else {
          $data['identifier'] = [];
        }
        $data['identifier'] = array_merge($data['identifier'], $identifiers);
      }
    }

    // Alter Schema.org type JSON-LD using the entity.
    // @see schemadotorg_jsonld_schema_type_entity_alter()
    $this->moduleHandler->alter('schemadotorg_jsonld_schema_type_entity', $data, $entity);

    // Sort Schema.org properties in specified order and then alphabetically.
    $data = $this->schemaJsonLdManager->sortProperties($data);

    // Return data if a Schema.org @type is defined.
    return (isset($data['@type']))
      ? $data
      : FALSE;
  }

  /**
   * Build JSON-LD for an entity that is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param bool $map_entity
   *   TRUE if the entity should be mapped.
   *   This helps prevent a mapping recursion.
   *
   * @return array|bool
   *   The JSON-LD for an entity that is mapped to a Schema.org type
   *   or FALSE if the entity is not mapped to a Schema.org type.
   */
  protected function buildMappedEntity(EntityInterface $entity, bool $map_entity = TRUE): array|bool {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    if (!$mapping_storage->isEntityMapped($entity)) {
      return [];
    }

    $type_data = [];

    $mapping = $mapping_storage->loadByEntity($entity);

    $schema_type = $mapping->getSchemaType();
    $schema_properties = $mapping->getSchemaProperties();
    foreach ($schema_properties as $field_name => $schema_property) {
      // Make sure the entity has the field and the current user has
      // access to the field.
      if (!$entity->hasField($field_name) || !$entity->get($field_name)->access('view')) {
        continue;
      }

      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $entity->get($field_name);

      // Get the Schema.org properties.
      $total_items = $items->count();
      $position = 1;
      $property_values = [];
      foreach ($items as $item) {
        $property_value = $this->getFieldItem($schema_type, $schema_property, $item, $map_entity);
        // Alter the Schema.org property's individual value.
        $this->moduleHandler->alter(
          'schemadotorg_jsonld_schema_property',
          $property_value,
          $item
        );

        // If there is more than 1 item, see if we need to its position.
        if ($total_items > 1) {
          $property_type = (is_array($property_value))
            ? $property_value['@type'] ?? NULL
            : NULL;
          if ($property_type
            && $this->schemaTypeManager->hasProperty($property_type, 'position')) {
            $property_value['position'] = $position;
            $position++;
          }
        }

        if ($property_value !== NULL) {
          $property_values[] = $property_value;
        }
      }

      // Alter the Schema.org property's values.
      $this->moduleHandler->alter(
        'schemadotorg_jsonld_schema_properties',
        $property_values,
        $items
      );

      // If the cardinality is 1, return the first property data item.
      $cardinality = $items->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality();
      if ($property_values) {
        $type_data[$schema_property] = ($cardinality === 1) ? reset($property_values) : $property_values;
      }
    }

    if (!$type_data) {
      return [];
    }

    // Prepend the @type to the returned data.
    $default_data = [];
    $default_data['@type'] = $mapping->getSchemaType();
    // Allow subtype field to override the mapping Schema.org type.
    // @see schemadotorg_subtype.module
    $subtype_field_name = $mapping->getSchemaPropertyFieldName('subtype');
    if ($subtype_field_name && $entity->hasField($subtype_field_name) && $entity->get($subtype_field_name)->value) {
      $default_data['@type'] = $entity->get($subtype_field_name)->value;
    }

    // Prepend the @url to the returned data.
    if ($entity->hasLinkTemplate('canonical') && $entity->access('view')) {
      $default_data['@url'] = $entity->toUrl('canonical')->setAbsolute()->toString();
    }

    $data = $default_data + $type_data;

    // Alter Schema.org type JSON-LD using the entity.
    // @see schemadotorg_jsonld_schema_type_entity_alter()
    foreach ($schema_properties as $field_name => $schema_property) {
      // Make sure the entity has the field and the current user has
      // access to the field.
      if (!$entity->hasField($field_name) || !$entity->get($field_name)->access('view')) {
        continue;
      }

      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $entity->get($field_name);
      $this->moduleHandler->alter('schemadotorg_jsonld_schema_type_field', $data, $items);
    }

    return $data;
  }

  /**
   * Get Schema.org type property data type from field item.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   * @param \Drupal\Core\Field\FieldItemInterface|null $item
   *   The field item.
   * @param bool $map_entity
   *   TRUE if entity should be mapped.
   *
   * @return mixed
   *   A data type.
   */
  protected function getFieldItem(string $schema_type, string $schema_property, ?FieldItemInterface $item = NULL, bool $map_entity = TRUE): mixed {
    if ($item === NULL) {
      return NULL;
    }

    // Handle entity reference relationships.
    if ($item->entity && $item->entity instanceof EntityInterface) {
      if (!$map_entity) {
        return NULL;
      }

      $has_url = !$item->entity->hasLinkTemplate('canonical');
      $entity_data = $this->buildMappedEntity($item->entity, $has_url);
      if ($entity_data) {
        return $entity_data;
      }
      else {
        return NULL;
      }
    }

    // Get Schema.org property value.
    $property_value = $this->schemaJsonLdManager->getSchemaPropertyValue($item);

    // Get Schema.org property value with the property's
    // default Schema.org type.
    return $this->schemaJsonLdManager->getSchemaPropertyValueDefaultType($schema_type, $schema_property, $property_value);
  }

  /**
   * Get Schema.org types from data.
   *
   * @param array $data
   *   An array of Schema.org data.
   *
   * @return array
   *   Schema.org types.
   */
  protected function getSchemaTypesFromData(array $data): array {
    $types = [];
    foreach ($data as $item) {
      if (is_array($item)) {
        if (isset($item['@type'])) {
          // Make sure all Schema.org types have @context.
          $types[] = ['@context' => 'https://schema.org'] + $item;
        }
        else {
          $types = array_merge($types, $this->getSchemaTypesFromData($item));
        }
      }
    }
    return $types;
  }

}
