<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\ImageStyleInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Schema.org JSON-LD manager.
 */
class SchemaDotOrgJsonLdManager implements SchemaDotOrgJsonLdManagerInterface {
  use StringTranslationTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

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
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

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
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgJsonLdManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RendererInterface $renderer,
    RouterInterface $router,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    FieldTypePluginManagerInterface $field_type_plugin_manager,
    DateFormatterInterface $date_formatter,
    FileUrlGeneratorInterface $file_url_generator,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->router = $router;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->dateFormatter = $date_formatter;
    $this->fileUrlGenerator = $file_url_generator;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityRouteMatch(EntityInterface $entity, string $rel = 'canonical'): RouteMatchInterface|null {
    if (!$entity->hasLinkTemplate($rel)) {
      return NULL;
    }

    $url = $entity->toUrl($rel);
    $route_name = $url->getRouteName();
    $route = $this->router->getRouteCollection()->get($route_name);
    if (empty($route)) {
      return NULL;
    }

    $entity_type_id = $entity->getEntityTypeId();
    return new RouteMatch(
      $route_name,
      $route,
      [$entity_type_id => $entity],
      [$entity_type_id => $entity->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteMatchEntity(?RouteMatchInterface $route_match = NULL): EntityInterface|null {
    $route_match = $route_match ?: $this->routeMatch;
    $route_name = $route_match->getRouteName();
    if (preg_match('/entity\.(.*)\.(latest[_-]version|canonical)/', $route_name, $matches)) {
      return $route_match->getParameter($matches[1]);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sortProperties(array $properties): array {
    $definition_properties = [];
    $sorted_properties = [];

    // Collect the definition properties.
    foreach ($properties as $property_name => $property_value) {
      if ($property_name[0] === '@') {
        $definition_properties[$property_name] = $property_value;
        unset($properties[$property_name]);
      }
    }

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

    return $definition_properties + $sorted_properties + $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyValue(FieldItemInterface $item): mixed {
    // Field type (from Drupal core only).
    $field_type = $this->getFieldType($item);
    switch ($field_type) {
      case 'language':
        return ($item->value !== LanguageInterface::LANGCODE_NOT_SPECIFIED) ? $item->value : NULL;

      case 'link':
        return $item->uri;

      case 'text_long':
      case 'text_with_summary':
        return (string) check_markup($item->value, $item->format);

      case 'image':
      case 'file':
        return $this->getImageDeriativeUrl($item) ?: $this->getFileUrl($item);

      case 'daterange':
        $entity_type_id = $item->getFieldDefinition()->getTargetEntityTypeId();
        $bundle = $item->getFieldDefinition()->getTargetBundle();
        $field_name = $item->getFieldDefinition()->getName();

        /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
        $mapping = $this->entityTypeManager
          ->getStorage('schemadotorg_mapping')
          ->load("$entity_type_id.$bundle");
        if (!$mapping) {
          return $item->value;
        }

        $schema_property = $mapping->getSchemaPropertyMapping($field_name);
        if ($schema_property === 'eventSchedule') {
          return [
            '@type' => 'Schedule',
            'startDate' => $item->value,
            'endDate' => $item->end_value,
          ];
        }
        else {
          return $item->value;
        }

      case 'decimal':
      case 'float':
      case 'integer':
        // @todo Determine if other field types should fully render each item.
        $field_type_info = $this->fieldTypePluginManager->getDefinition($field_type);
        $display_options = ['type' => $field_type_info['default_formatter']];
        $build = $item->view($display_options);
        return (string) $this->renderer->renderPlain($build);
    }

    // Main property data type.
    $value = $this->getFieldMainPropertyValue($item);
    if (!is_array($value)) {
      $main_property_data_type = $this->getMainPropertyDateType($item);
      switch ($main_property_data_type) {
        case 'timestamp':
          return ($value)
            ? $this->dateFormatter->format($value, 'custom', 'Y-m-d H:i:s P')
            : $value;
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
   * {@inheritdoc}
   */
  public function getSchemaPropertyValueDefaultType(string $type, string $property, mixed $value): array|string {
    $default_property_values = $this->configFactory
      ->get('schemadotorg.settings')
      ->get('schema_types.default_property_values');

    if (is_array($value)) {
      $value_type = $value['@type'] ?? '';
      $default_values = $default_property_values[$value_type] ?? [];
      return $value + $default_values;
    }

    $range_includes = $this->configFactory
      ->get('schemadotorg.settings')
      ->get("schema_properties.range_includes.$type--$property");
    if ($range_includes) {
      $property_type = reset($range_includes);
    }
    else {
      $property_type = $this->schemaTypeManager->getPropertyDefaultType($property);
    }

    if (!$property_type) {
      return $value;
    }

    $main_property = $this->getSchemaTypeMainProperty($property_type);
    if (!$main_property) {
      return $value;
    }

    $default_values = $default_property_values[$property_type] ?? [];
    return [
      '@type' => $property_type,
      $main_property => $value,
    ] + $default_values;
  }

  /**
   * Get Schema.org type's main property.
   *
   * @param string $type
   *   The Schema.org type.
   *
   * @return string
   *   A Schema.org type's main property. (Defaults to 'name')
   */
  protected function getSchemaTypeMainProperty(string $type): string {
    $main_properties = $this->configFactory
      ->get('schemadotorg.settings')
      ->get('schema_types.main_properties');

    $breadcrumbs = $this->schemaTypeManager->getTypeBreadcrumbs($type);
    foreach ($breadcrumbs as $breadcrumb) {
      $breadcrumb = array_reverse($breadcrumb);
      foreach ($breadcrumb as $type) {
        // Using array key exists to account main property being set to NULL,
        // which means the Schema.org type does NOT have a main property.
        if (array_key_exists($type, $main_properties)) {
          return $main_properties[$type];
        }
      }
    }

    return 'name';
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaIdentifiers(EntityInterface $entity): array {
    $identifiers = $this->getConfig()->get('identifiers');

    $entity_data = [];
    if ($entity instanceof ContentEntityInterface) {
      foreach ($identifiers as $field_name => $identifier) {
        // Make sure the entity has the field and the current user has
        // access to the field.
        if (!$entity->hasField($field_name)
          || !$entity->get($field_name)->access('view')) {
          continue;
        }

        /** @var \Drupal\Core\Field\FieldItemListInterface $items */
        $items = $entity->get($field_name);
        foreach ($items as $item) {
          $value = $this->getSchemaPropertyValue($item);
          if ($value) {
            $entity_data += [$identifier => []];
            $entity_data[$identifier][] = $value;
          }
        }
      }
    }
    elseif ($entity instanceof ConfigEntityInterface) {
      foreach ($identifiers as $field_name => $identifier) {
        $value = $entity->get($field_name);
        if ($value) {
          $entity_data += [$identifier => []];
          $entity_data[$identifier][] = $value;
        }
      }
    }

    $schema_data = [];
    foreach ($entity_data as $identifier => $items) {
      foreach ($items as $item) {
        $schema_data[] = [
          '@type' => 'PropertyValue',
          'propertyID' => $identifier,
          'value' => $item,
        ];
      }
    }
    return $schema_data;
  }

  /**
   * Gets Schema.org JSON-LD configuration settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Schema.org JSON-LD configuration settings.
   */
  protected function getConfig(): ImmutableConfig {
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
  protected function getEntity(FieldItemInterface $item): FieldableEntityInterface {
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
  protected function getFieldName(FieldItemInterface $item): string {
    return $item->getFieldDefinition()->getName();
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
  protected function getFieldType(FieldItemInterface $item): string {
    return $item->getFieldDefinition()->getFieldStorageDefinition()->getType();
  }

  /**
   * Gets the field values for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return mixed
   *   The field values for a field item.
   */
  protected function getFieldValue(FieldItemInterface $item): mixed {
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
   * @return mixed
   *   The field values or main property's value for a field item.
   */
  protected function getFieldMainPropertyValue(FieldItemInterface $item): mixed {
    $values = $this->getFieldValue($item);
    if (empty($values)) {
      return NULL;
    }

    $main_property_name = $this->getMainPropertyName($item);
    return $values[$main_property_name] ?? NULL;
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
  protected function getPropertyNames(FieldItemInterface $item): array {
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
  protected function getMainPropertyName(FieldItemInterface $item): string {
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
  protected function getMainPropertyDateType(FieldItemInterface $item): ?string {
    $field_storage_definition = $item->getFieldDefinition()->getFieldStorageDefinition();
    $main_property_name = $field_storage_definition->getMainPropertyName();
    $main_property_definition = $field_storage_definition->getPropertyDefinition($main_property_name);
    return $main_property_definition ? $main_property_definition->getDataType() : NULL;
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
  protected function getSchemaProperty(FieldItemInterface $item): string {
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
  protected function getFileUri(FieldItemInterface $item): string {
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
  protected function getFileUrl(FieldItemInterface $item): string {
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
  protected function getImageStyle(FieldItemInterface $item): ImageStyleInterface|null {
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
   * @return string|null
   *   The image deriative URL for a field item.
   */
  protected function getImageDeriativeUrl(FieldItemInterface $item): ?string {
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

}
