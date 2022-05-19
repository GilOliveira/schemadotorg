<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Schema.org JSON-LD builder.
 */
class SchemaDotOrgJsonLdBuilder implements SchemaDotOrgJsonLdBuilderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a SchemaDotOrgJsonLdBuilder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;

    $this->moduleHandler->loadAllIncludes('schemadotorg.inc');
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $entity) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    if (!$mapping_storage->isEntityMapped($entity)) {
      return FALSE;
    }

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

    // Get properties without any additional meta data.
    $field_storage_definition = $item->getFieldDefinition()->getFieldStorageDefinition();
    $property_names = $field_storage_definition->getPropertyNames();
    $values = array_intersect_key($item->getValue(), array_combine($property_names, $property_names));

    // If there is only one main property return it,
    // otherwise return all the properties.
    $main_property_name = $field_storage_definition->getMainPropertyName();
    if (count($property_names) === 1 && isset($values[$main_property_name])) {
      return $values[$main_property_name];
    }
    else {
      return $values;
    }
  }

}
