<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "schemadotorg_mapping_type" configuration entities.
 */
class SchemaDotOrgMappingTypeStorage extends ConfigEntityStorage implements SchemaDotOrgMappingTypeStorageInterface {

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
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    $entity_type_ids = array_keys($this->loadMultiple());
    $entity_types = [];
    foreach ($entity_type_ids as $entity_type_id) {
      if ($this->entityTypeManager->hasDefinition($entity_type_id)) {
        $entity_types[$entity_type_id] = $entity_type_id;
      }
    }
    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypesWithBundles() {
    $entity_type_ids = array_keys($this->loadMultiple());
    $entity_types = [];
    foreach ($entity_type_ids as $entity_type_id) {
      if ($this->entityTypeManager->hasDefinition($entity_type_id)
        && $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType()) {
        $entity_types[$entity_type_id] = $entity_type_id;
      }
    }
    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaTypeBundles($entity_type_id, $type) {
    $schema_types = $this->getEntityTypeProperty($entity_type_id, 'default_schema_types');
    $bundles = [];
    foreach ($schema_types as $bundle => $schema_type) {
      if ($type === $schema_type) {
        $bundles[$bundle] = $bundle;
      }
    }
    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaType($entity_type_id, $bundle) {
    $schema_types = $this->getEntityTypeProperty($entity_type_id, 'default_schema_types');
    return $schema_types[$bundle] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaTypeProperties($entity_type_id, $schema_type) {
    $type_properties = $this->getEntityTypeProperty($entity_type_id, 'default_schema_type_properties');
    if (empty($type_properties)) {
      return NULL;
    }

    $breadcrumbs = $this->schemaTypeManager->getTypeBreadcrumbs($schema_type);
    $default_properties = [];
    foreach ($breadcrumbs as $breadcrumb) {
      foreach ($breadcrumb as $breadcrumb_type) {
        $breadcrumb_type_properties = $type_properties[$breadcrumb_type] ?? NULL;
        if ($breadcrumb_type_properties) {
          $default_properties += array_combine($breadcrumb_type_properties, $breadcrumb_type_properties);
        }
      }
    }
    ksort($default_properties);
    return $default_properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaTypeSubtypes($entity_type_id) {
    return $this->getEntityTypeProperty($entity_type_id, 'default_schema_type_subtypes');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldWeights($entity_type_id) {
    $weights = $this->getEntityTypeProperty($entity_type_id, 'default_field_weights');
    $weights = array_flip($weights);
    // Start field weights at 1 since most default fields are set to 0.
    array_walk($weights, function (&$weight) {
      $weight += 1;
    });
    return $weights;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldGroups($entity_type_id) {
    return $this->getEntityTypeProperty($entity_type_id, 'default_field_groups');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldGroupLabelSuffix($entity_type_id) {
    return $this->getEntityTypeProperty($entity_type_id, 'default_field_group_label_suffix');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldGroupFormatType($entity_type_id, EntityDisplayInterface $display) {
    $display_type = ($display instanceof EntityFormDisplayInterface) ? 'form' : 'view';
    return $this->getEntityTypeProperty($entity_type_id, 'default_field_group_' . $display_type . '_type', '');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldGroupFormatSettings($entity_type_id, EntityDisplayInterface $display) {
    $type = $this->getDefaultFieldGroupFormatType($entity_type_id, $display);
    switch ($type) {
      case 'details':
        return ['open' => TRUE];

      case 'fieldset':
      case 'html_element':
      default:
        return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultiple($entity_type_id) {
    return $this->getEntityTypeProperty($entity_type_id, 'multiple');
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendedSchemaTypes($entity_type_id) {
    return $this->getEntityTypeProperty($entity_type_id, 'recommended_schema_types');
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldMappings($entity_type_id) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return [];
    }

    $base_fields = $mapping_type->get('default_base_fields') ?: [];
    $base_fields = array_filter($base_fields);
    if (empty($base_fields)) {
      return [];
    }

    $mappings = [];
    foreach ($base_fields as $field_name => $properties) {
      foreach ($properties as $property) {
        $mappings[$property][$field_name] = $field_name;
      }
    }
    return $mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldNames($entity_type_id) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return [];
    }

    $default_base_fields = $mapping_type->get('default_base_fields') ?: [];
    $base_field_names = array_keys($default_base_fields);
    return array_combine($base_field_names, $base_field_names);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeBundles() {
    $entity_types = $this->getEntityTypes();

    $items = [];
    foreach ($entity_types as $entity_type_id) {
      // Make sure the entity is supported.
      if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
        continue;
      }

      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      // Make sure the entity has a field UI.
      $route_name = $entity_type->get('field_ui_base_route');
      if (!$route_name) {
        continue;
      }

      // Media bundles are not support because the add media form is
      // not reusable.
      if ($entity_type_id === 'media') {
        continue;
      }

      // Make sure the bundle entity exists.
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      if (!$bundle_entity_type_id) {
        continue;
      }

      $items[$entity_type_id] = $entity_type;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeBundleDefinitions() {
    $items = [];
    $entity_types = $this->getEntityTypeBundles();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      $items[$entity_type_id] = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
    }
    return $items;
  }

  /**
   * Gets an entity type's property with a default value.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $property_name
   *   The property name.
   * @param mixed $default_value
   *   The default value.
   *
   * @return mixed
   *   An entity type's property or the default value.
   */
  protected function getEntityTypeProperty($entity_type_id, $property_name, $default_value = []) {
    $mapping_type = $this->load($entity_type_id);
    if (!$mapping_type) {
      return $default_value;
    }
    else {
      return $mapping_type->get($property_name) ?: $default_value;
    }
  }

}
