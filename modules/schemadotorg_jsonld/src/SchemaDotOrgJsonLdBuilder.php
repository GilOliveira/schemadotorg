<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager
   *   The Schema.org JSON-LD manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaJsonIdManager = $schema_jsonld_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $entity) {
    $data = $this->buildEntityData($entity);
    if (!$data) {
      return FALSE;
    }

    // Prepend the @context to the returned data.
    return ['@context' => 'https://schema.org'] + $data;
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
  protected function buildEntityData(EntityInterface $entity) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    if (!$mapping_storage->isEntityMapped($entity)) {
      return FALSE;
    }

    $schema_type_data = [];

    $mapping = $mapping_storage->loadByEntity($entity);
    $schema_type = $mapping->getSchemaType();
    $schema_properties = $mapping->getSchemaProperties();
    foreach ($schema_properties as $field_name => $schema_property) {
      // Make sure the entity has the field.
      if (!$entity->hasField($field_name)) {
        continue;
      }

      // Make sure the user has access to the field.
      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $entity->get($field_name);
      if (!$items->access('view')) {
        continue;
      }

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

    // Add UUID as an identifier.
    // @todo Detemine if this should be optional.
    $schema_type_data += ['identifier' => []];
    $schema_type_data['identifier']['uuid'] = $entity->uuid();

    // Sort Schema.org properties in specified order and then alphabetically.
    $schema_type_data = $this->schemaJsonIdManager->sortProperties($schema_type_data);

    // Prepend the @type and @url to the returned data.
    $default_data = ['@type' => $schema_type];
    if ($entity->hasLinkTemplate('canonical') && $entity->access('view')) {
      $default_data['@url'] = $entity->toUrl('canonical')->setAbsolute()->toString();
    }
    $schema_type_data = $default_data + $schema_type_data;

    // Alter Schema.org type's JSON-LD data.
    $this->moduleHandler->alter(
      'schemadotorg_jsonld_schema_type',
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
      $entity_data = $this->buildEntityData($item->entity);
      if ($entity_data) {
        return $entity_data;
      }
    }

    // Get Schema.org property value.
    return $this->schemaJsonIdManager->getSchemaPropertyValue($item);
  }

}
