<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\file\FileInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
use phpDocumentor\Reflection\Types\False_;

/**
 * Schema.org JSON-LD builder.
 */
class SchemaDotOrgJsonLdBuilder implements SchemaDotOrgJsonLdBuilderInterface {
  use StringTranslationTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a SchemaDotOrgJsonLd object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $field_manager
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
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
    return ($data) ? ['@context' => 'https://schema.org'] + $data : [];
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

    $data = [];

    $mapping = $mapping_storage->loadByEntity($entity);
    $type = $mapping->getSchemaType();
    $properties = $mapping->getSchemaProperties();
    foreach ($properties as $field_name => $property) {
      if ($entity->hasField($field_name)) {
        $property_data = $this->getSchemaPropertyDataFromFieldItems($property, $entity->get($field_name));
        if ($property_data) {
          $data[$property] = $property_data;
        }
      }
    }

    return ($data) ? ['@type' => $type] + $data : [];
  }

  /**
   * Get Schema.org property data from field items.
   *
   * @param string $property
   *   The Schema.org property.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   FieldItemList containing the values to be displayed.
   *
   * @return array
   *   An array containing the Schema.org property data.
   */
  protected function getSchemaPropertyDataFromFieldItems($property, FieldItemListInterface $items) {
    $field_definition = $items->getFieldDefinition();
    $field_storage_definition = $field_definition->getFieldStorageDefinition();

    $property_names = $field_storage_definition->getPropertyNames();
    $property_names = array_combine($property_names, $property_names);

    // Entity references.
    // Files.
    // Properties.
    if ($field_storage_definition->getCardinality() === 1) {
      return $this->getSchemaPropertyDataFromFieldItem($property, $items->get(0));
    }
    else {
      $data = [];
      foreach ($items as $item) {
        $data[] = $this->getSchemaPropertyDataFromFieldItem($property, $item);
      }
      return $data;
    }
  }

  /**
   * Get Schema.org property data type from field item.
   *
   * @param string $property
   *   The Schema.org property.
   * @param \Drupal\Core\Field\FieldItemInterface|null $item
   *   The field item.
   *
   * @return array|bool|mixed|null
   *   A data type.
   */
  protected function getSchemaPropertyDataFromFieldItem($property, FieldItemInterface $item = NULL) {
    if ($item === NULL) {
      return NULL;
    }

    $config = $this->configFactory->get('schemadotorg_jsonld.settings');

    $field_storage_definition = $item->getFieldDefinition()->getFieldStorageDefinition();
    $field_type = $field_storage_definition->getType();
    $property_names = $field_storage_definition->getPropertyNames();
    $property_names = array_combine($property_names, $property_names);

    // Handle file and entity references.
    if ($item->entity && $item->entity instanceof EntityInterface) {
      if ($item->entity instanceof FileInterface) {
        $file_uri = $item->entity->getFileUri();
        // Return image style.
        $style = $config->get('property_image_styles.' . $property);
        if ($field_type === 'image' && $style) {
          $image_style = $this->entityTypeManager->getStorage('image_style')->load($style);
          if ($image_style) {
            return $image_style->buildUrl($file_uri);
          }
        }
        return \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri);
      }
      else {
        return $this->buildEntityData($item->entity);
      }
    }

    // Handle properties.
    $values = array_intersect_key($item->getValue(), $property_names);

    // Return a specified Schema.org type with properties if it is defined.
    $mapping = $config->get('field_type_mappings.' . $field_type);
    if ($mapping) {
      $data = ['@type' => $mapping['type']];
      foreach ($mapping['properties'] as $field_property => $schema_property) {
        if ($schema_property && isset($values[$field_property]) && !is_null($values[$field_property])) {
          $data[$schema_property] = $values[$field_property];
        }
      }
      return $data;
    }

    // Return a specified field type property if it is defined.
    $property_name = $config->get('field_type_properties.' . $field_type);
    if ($property_name && isset($values[$property_name])) {
      return $values[$property_name];
    }

    // Default to returning the first property when possible.
    return (count($values) === 1) ? reset($values) : $values;
  }

}
