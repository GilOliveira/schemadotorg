<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;

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
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    DateFormatterInterface $date_formatter,
    FileUrlGeneratorInterface $file_url_generator,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->dateFormatter = $date_formatter;
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

    $data = [];

    $mapping = $mapping_storage->loadByEntity($entity);
    $type = $mapping->getSchemaType();
    $properties = $mapping->getSchemaProperties();
    foreach ($properties as $field_name => $property) {
      if ($entity->hasField($field_name)) {
        $field_items = $entity->get($field_name);
        if (!$field_items->access('view')) {
          continue;
        }

        $property_data = [];
        foreach ($field_items as $field_item) {
          $property_data[] = $this->getFieldItem($property, $field_item);
        }

        // @todo Add entity alter hooks.
        // @see HOOK_schemadotorg_jsonld_entity_alter()
        // @see HOOK_schemadotorg_jsonld_entity_ENTITY_TYPE_alter()
        // @todo Add field type alter hooks.
        // @see HOOK_schemadotorg_jsonld_field_type_alter()
        // @see HOOK_schemadotorg_jsonld_field_type_FIELD_TYPE_alter()
        // @todo Add field name alter hooks.
        // @see HOOK_schemadotorg_jsonld_field_name_alter()
        // @see HOOK_schemadotorg_jsonld_field_name_FIELD_NAME_alter()
        // @todo Add datatype alter hooks.
        // @see HOOK_schemadotorg_jsonld_datatype_alter()
        // @see HOOK_schemadotorg_jsonld_datatype_DATATYPE_alter()
        // @todo Add property alter hooks.
        // @see HOOK_schemadotorg_jsonld_property_alter()
        // @see HOOK_schemadotorg_jsonld_property_PROPERTY_alter()

        // If the cardinality is 1, return the first property data item.
        $cardinality = $field_items
          ->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getCardinality();

        if ($property_data) {
          $data[$property] = ($cardinality === 1) ? reset($property_data) : $property_data;
        }
      }
    }

    if (!$data) {
      return FALSE;
    }

    // Prepend the @type and @url to the returned data.
    $default_data = ['@type' => $type];
    if ($entity->hasLinkTemplate('canonical')
      && $entity->access('view')) {
      $default_data['@url'] = $entity->toUrl('canonical')->setAbsolute()->toString();
    }
    $data = $default_data + $data;

    // @todo Add alter hook.
    // hook_schemadotorg_jsonld_entity_alter(&$data, EntityInterface $entity, SchemaDotOrgMapping $mapping);
    return $data;
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
  protected function getFieldItem($property, FieldItemInterface $item = NULL) {
    if ($item === NULL) {
      return NULL;
    }

    $config = $this->configFactory->get('schemadotorg_jsonld.settings');

    $field_storage_definition = $item->getFieldDefinition()->getFieldStorageDefinition();
    $field_type = $field_storage_definition->getType();

    $property_names = $field_storage_definition->getPropertyNames();
    $property_names = array_combine($property_names, $property_names);

    // Handle file and entity references.
    // @todo Add entity alter hooks.
    // @see HOOK_schemadotorg_jsonld_item_entity_alter()
    // @see HOOK_schemadotorg_jsonld_item_entity_ENTITY_TYPE_alter()
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
    // @todo Add field type alter hooks.
    // @see HOOK_schemadotorg_jsonld_item_field_type_alter()
    // @see HOOK_schemadotorg_jsonld_item_field_type_FIELD_TYPE_alter()
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

    // Return a specified field type property and format if it is defined.
    // @todo Add field type alter hooks.
    // @see HOOK_schemadotorg_jsonld_item_type_alter()
    // @see HOOK_schemadotorg_jsonld_item_type_FIELD_TYPE_alter()
    $property_name = $config->get('field_type_properties.' . $field_type);
    if ($property_name) {
      // Get the property's format for processing the returned value.
      if (strpos($property_name, '--') !== FALSE) {
        [$property_name, $format] = explode('--', $property_name);
      }
      else {
        $format = NULL;
      }

      // @todo Determine if we want to support other formats like plain-text.
      switch ($format) {
        case 'processed':
          return check_markup($values[$property_name], $values['format']);

        default:
          return $values[$property_name];
      }
    }

    // Handle property data types.
    // @todo Add datatype alter hooks.
    // @see HOOK_schemadotorg_jsonld_item_datatype_alter()
    // @see HOOK_schemadotorg_jsonld_item_datatype_DATATYPE_alter()
    $property_definitions = $field_storage_definition->getPropertyDefinitions();
    if (count($property_definitions) === 1) {
      $property_definition = reset($property_definitions);
      $value = reset($values);
      switch ($property_definition->getDataType()) {
        case 'timestamp';
          return $this->dateFormatter->format($value, 'custom', 'Y-m-d H:i:s P');

        default:
          return $value;
      }
    }

    // Default to returning the first property when possible.
    return (count($values) === 1) ? reset($values) : $values;
  }

}
