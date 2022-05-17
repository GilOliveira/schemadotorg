<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;

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
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SchemaDotOrgJsonLd object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    FileUrlGeneratorInterface $file_url_generator,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->fileUrlGenerator = $file_url_generator;
    $this->entityTypeManager = $entity_type_manager;
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

    if (!$data) {
      return FALSE;
    }

    $default_data = ['@type' => $type];
    if ($entity->hasLinkTemplate('canonical')
      && $entity->access('view')) {
      $default_data['@url'] = $entity->toUrl('canonical')->setAbsolute()->toString();
    }
    return $default_data + $data;
  }

  /**
   * Get Schema.org property data from field items.
   *
   * @param string $property
   *   The Schema.org property.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   FieldItemList containing the values to be displayed.
   *
   * @return array|null
   *   An array containing the Schema.org property data.
   *   NULL if the user does not have access to the field.
   */
  protected function getSchemaPropertyDataFromFieldItems($property, FieldItemListInterface $items) {
    if (!$items->access('view')) {
      return NULL;
    }

    $field_definition = $items->getFieldDefinition();
    $field_storage_definition = $field_definition->getFieldStorageDefinition();
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

        // Return an image style URL.
        $style = $config->get('property_image_styles.' . $property);
        if ($field_type === 'image' && $style) {
          $image_style_storage = $this->entityTypeManager->getStorage('image_style');
          $image_style = $image_style_storage->load($style);
          if ($image_style) {
            return $image_style->buildUrl($file_uri);
          }
        }

        // Default the file's URL.
        return $this->fileUrlGenerator->generateAbsoluteString($file_uri);
      }
      else {
        return $this->buildEntityData($item->entity) ?: $item->entity->label();
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
          if (isset($data[$schema_property])) {
            $data[$schema_property] .= ' ' . $values[$field_property];
          }
          else {
            $data[$schema_property] = $values[$field_property];
          }
        }
      }
      return $data;
    }

    // Return a specified field type property if it is defined.
    $property_name = $config->get('field_type_properties.' . $field_type);
    if ($property_name && isset($values[$property_name])) {
      return $values[$property_name];
    }

    // Handle property data types.
    $property_definitions = $field_storage_definition->getPropertyDefinitions();
    if (count($property_definitions) === 1) {
      $property_definition = reset($property_definitions);
      $value = reset($values);
      switch ($property_definition->getDataType()) {
        case 'timestamp';
          return \Drupal::service('date.formatter')->format($value, 'custom', 'Y-m-d H:i:s P');

        default:
          return $value;
      }
    }

    // Default to returning the first property when possible.
    return (count($values) === 1) ? reset($values) : $values;
  }

}
