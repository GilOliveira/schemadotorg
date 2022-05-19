<?php

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Schema.org JSON-LD manager.
 */
class SchemaDotOrgJsonLdManager implements SchemaDotOrgJsonLdManagerInterface {

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
   * The date formatter service.
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
   * Constructs a SchemaDotOrgJsonLdManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    FileUrlGeneratorInterface $file_url_generator
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function sortProperties(array $properties) {
    $sorted_properties = [];

    // Collect the sorted properties.
    $property_order = $this->getConfig()->get('property_order');
    foreach ($property_order as $property_name) {
      if (isset($properties[$property_name])) {
        $sorted_properties[$property_name] = $properties[$property_name];
        unset($properties[$property_name]);
      }
    }

    // Sort the remaining properties alphabetically.
    ksort($properties);

    return $sorted_properties + $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyValue(FieldItemInterface $item) {
    // Field type.
    $field_type = $this->getFieldType($item);
    switch ($field_type) {
      case 'address':
        $mapping = [
          'country_code' => 'addressCountry',
          'administrative_area' => 'addressRegion',
          'locality' => 'addressLocality',
          'dependent_locality' => 'addressLocality',
          'postal_code' => 'postalCode',
          'sorting_code' => 'postOfficeBoxNumber',
          'address_line1' => 'streetAddress',
          'address_line2' => 'streetAddress',
          'organization' => 'name',
          'given_name' => 'name',
          'additional_name' => 'name',
          'family_name' => 'name',
        ];
        return $this->mapSchemaType($item, 'PostalAddress', $mapping);

      case 'link':
        return $item->uri;

      case 'text_long':
      case 'text_with_summary':
        return (string) check_markup($item->value, $item->format);

      case 'image':
      case 'file':
        return $this->getImageDeriativeUrl($item) ?: $this->getFileUrl($item);
    }

    // Main property data type.
    $value = $this->getFieldMainPropertyValue($item);
    if (!is_array($value)) {
      $main_property_data_type = $this->getMainPropertyDateType($item);
      switch ($main_property_data_type) {
        case 'timestamp':
          return $this->dateFormatter->format($value, 'custom', 'Y-m-d H:i:s P');
      }
    }

    // Entity reference that are not mapped to Schema.org type.
    // @todo Determine the best way to handle an unmapped entity reference.
    if ($item->entity && $item->entity instanceof EntityInterface) {
      return $item->entity->label();
    }

    return $value;
  }

  /**
   * Gets Schema.org JSON-LD configuration settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Schema.org JSON-LD configuration settings.
   */
  protected function getConfig() {
    return $this->configFactory->get('schemadotorg_jsonld.settings');
  }

  /**
   * Gets the entity for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity for a field item.
   */
  protected function getEntity(FieldItemInterface $item) {
    return $item->getEntity();
  }

  /**
   * Gets the field name for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The field name for a field item.
   */
  protected function getFieldName(FieldItemInterface $item) {
    return $item->getName();
  }

  /**
   * Gets the field type for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The field type for a field item.
   */
  protected function getFieldType(FieldItemInterface $item) {
    return $item->getFieldDefinition()->getFieldStorageDefinition()->getType();
  }

  /**
   * Gets the field values for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return array|mixed
   *   The field values for a field item.
   */
  protected function getFieldValue(FieldItemInterface $item) {
    $property_names = $this->getPropertyNames($item);
    $property_names = array_combine($property_names, $property_names);
    return array_intersect_key($item->getValue(), $property_names);
  }

  /**
   * Gets the field values or main property's value for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return array|mixed
   *   The field values or main property's value for a field item.
   */
  protected function getFieldMainPropertyValue(FieldItemInterface $item) {
    $values = $this->getFieldValue($item);
    $main_property_name = $this->getMainPropertyName($item);
    return $values[$main_property_name];
  }

  /**
   * Gets the property names for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string[]
   *   The property names for a field item.
   */
  protected function getPropertyNames(FieldItemInterface $item) {
    return $item->getFieldDefinition()->getFieldStorageDefinition()->getPropertyNames();
  }

  /**
   * Gets the main property name for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The main property name for a field item.
   */
  protected function getMainPropertyName(FieldItemInterface $item) {
    return $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
  }

  /**
   * Gets the main property date type for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The main property date type for a field item.
   */
  protected function getMainPropertyDateType(FieldItemInterface $item) {
    $field_storage_definition = $item->getFieldDefinition()->getFieldStorageDefinition();
    $main_property_name = $field_storage_definition->getMainPropertyName();
    $main_property_definition = $field_storage_definition->getPropertyDefinition($main_property_name);
    return $main_property_definition->getDataType();
  }

  /**
   * Gets the mapped Schema.org property for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The mapped Schema.org property for a field item.
   */
  protected function getSchemaProperty(FieldItemInterface $item) {
    $entity = $this->getEntity($item);
    $field_name = $this->getFieldName($item);
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    $mapping = $mapping_storage->loadByEntity($entity);
    return $mapping->getSchemaPropertyMapping($field_name);
  }

  /**
   * Gets the file URI for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The file URI for a field item.
   */
  protected function getFileUri(FieldItemInterface $item) {
    return $item->entity->getFileUri();
  }

  /**
   * Gets the file URL for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The file URL for a field item.
   */
  protected function getFileUrl(FieldItemInterface $item) {
    $uri = $this->getFileUri($item);
    return $this->fileUrlGenerator->generateAbsoluteString($uri);
  }

  /**
   * Gets the selected image style for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return \Drupal\image\ImageStyleInterface|null
   *   The selected image style for a field item.
   */
  protected function getImageStyle(FieldItemInterface $item) {
    $schema_property = $this->getSchemaProperty($item);
    $style = $this->getConfig()->get('property_image_styles.' . $schema_property);
    if (!$style) {
      return NULL;
    }

    $image_style_storage = $this->entityTypeManager->getStorage('image_style');
    return $image_style_storage->load($style);
  }

  /**
   * Gets the image deriative URL for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The image deriative URL for a field item.
   */
  protected function getImageDeriativeUrl(FieldItemInterface $item) {
    $field_type = $this->getFieldType($item);
    if ($field_type !== 'image') {
      return NULL;
    }
    $image_style = $this->getImageStyle($item);
    if (!$image_style) {
      return NULL;
    }
    $file_uri = $item->entity->getFileUri();
    return $image_style->buildUrl($file_uri);
  }

  /**
   * Map a field item's values to Schema.org type and properties.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   * @param string $type
   *   The Schema.org type.
   * @param array $mapping
   *   An associative array containing mappings from field names
   *   to Schema.org properties.
   *
   * @return array
   *   A field item's values mapped to Schema.org type and properties.
   */
  protected function mapSchemaType(FieldItemInterface $item, $type, array $mapping) {
    $values = $item->getValue();

    $properties = [];
    foreach ($mapping as $source => $destination) {
      if ($destination && !empty($values[$source])) {
        if (isset($properties[$destination])) {
          $properties[$destination] .= ' ' . $values[$source];
        }
        else {
          $properties[$destination] = $values[$source];
        }
      }
    }

    return ['@type' => $type] + $this->sortProperties($properties);
  }

}
