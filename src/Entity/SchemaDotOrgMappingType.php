<?php

namespace Drupal\schemadotorg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface;

/**
 * Defines the Schema.org mapping type entity type.
 *
 * @ConfigEntityType(
 *   id = "schemadotorg_mapping_type",
 *   label = @Translation("Schema.org mapping type"),
 *   label_collection = @Translation("Schema.org mapping types"),
 *   label_singular = @Translation("Schema.org mapping type"),
 *   label_plural = @Translation("Schema.org mapping types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Schema.org mapping type",
 *     plural = "@count Schema.org mapping types",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage",
 *     "list_builder" = "Drupal\schemadotorg\SchemaDotOrgMappingTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingTypeForm",
 *       "edit" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "schemadotorg_mapping_type",
 *   admin_permission = "administer schemadotorg",
 *   links = {
 *     "collection" = "/admin/config/search/schemadotorg-mapping/type",
 *     "add-form" = "/admin/config/search/schemadotorg-mapping/type/add",
 *     "edit-form" = "/admin/config/search/schemadotorg-mapping/type/{schemadotorg_mapping_type}",
 *     "delete-form" = "/admin/config/search/schemadotorg-mapping/type/{schemadotorg_mapping_type}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "target_entity_type_id",
 *     "multiple",
 *     "recommended_schema_types",
 *     "default_schema_types",
 *     "default_schema_type_properties",
 *     "default_base_fields",
 *     "default_field_weights",
 *   }
 * )
 */
class SchemaDotOrgMappingType extends ConfigEntityBase implements SchemaDotOrgMappingTypeInterface {

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  protected $id;

  /**
   * Entity type to be displayed.
   *
   * @var string
   */
  protected $target_entity_type_id;

  /**
   * An associative array of default Schema.org types.
   *
   * @var array
   */
  protected $default_schema_types = [];

  /**
   * An associative array of default Schema.org type properties.
   *
   * @var array
   */
  protected $default_schema_type_properties = [];

  /**
   * An associative array of base field mappings.
   *
   * @var array
   */
  protected $default_base_fields = [];

  /**
   * An array of default field weights for Schema.org properties.
   *
   * @var array
   */
  protected $default_field_weights = [];

  /**
   * An associative array of grouped recommended Schema.org types.
   *
   * @var array
   */
  protected $recommended_schema_types = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->target_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $entity_type_manager = \Drupal::entityTypeManager();
    return $entity_type_manager->hasDefinition($this->id())
      ? $entity_type_manager->getDefinition($this->id())->getLabel()
      : $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaTypeBundles($type) {
    $schema_types = $this->get('default_schema_types');
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
  public function getDefaultSchemaType($bundle) {
    $schema_types = $this->get('default_schema_types');
    return $schema_types[$bundle] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaTypeProperties($schema_type) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager */
    $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');

    // Get global default Schema.org type properties.
    $type_properties = \Drupal::config('schemadotorg.settings')
      ->get('schema_types.default_properties');

    // Get mapping type default Schema.org type properties.
    $mapping_type_properties = $this->get('default_schema_type_properties');

    $default_properties = [];

    $breadcrumbs = $schema_type_manager->getTypeBreadcrumbs($schema_type);
    foreach ($breadcrumbs as $breadcrumb) {
      foreach ($breadcrumb as $breadcrumb_type) {
        $this->setSchemaTypeDefaultProperties($default_properties, $type_properties, $breadcrumb_type);
        $this->setSchemaTypeDefaultProperties($default_properties, $mapping_type_properties, $breadcrumb_type);
      }
    }

    // For 'Intangible' Schema.org types, we can default to all the properties
    // except  those inherited from 'Thing'.
    if (empty($default_properties) && $schema_type_manager->isIntangible($schema_type)) {
      $thing_properties = $schema_type_manager->getTypeProperties('Thing');
      $intangible_properties = $schema_type_manager->getTypeProperties($schema_type);
      $default_properties = array_diff_key($intangible_properties, $thing_properties);
      if ($default_properties) {
        $default_properties = array_keys($default_properties);
        $default_properties = array_combine($default_properties, $default_properties);
      }
      else {
        $default_properties = ['name' => 'name'];
      }
    }

    ksort($default_properties);
    return $default_properties;
  }

  /**
   * Set Schema.org type default properties.
   *
   * @param array $default_properties
   *   An associative array of default properties.
   * @param array $properties
   *   An associative array of properties keyed by Schema.org type.
   * @param string $type
   *   The Schema.org type.
   */
  protected function setSchemaTypeDefaultProperties(array &$default_properties, array $properties, $type) {
    if (!isset($properties[$type])) {
      return;
    }

    foreach ($properties[$type] as $property) {
      if ($property[0] === '-') {
        unset($default_properties[substr($property, 1)]);
      }
      else {
        $default_properties[$property] = $property;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldWeights() {
    $weights = $this->get('default_field_weights');
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
  public function supportsMultiple() {
    return $this->get('multiple');
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendedSchemaTypes() {
    return $this->get('recommended_schema_types');
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldMappings() {
    $base_fields = $this->get('default_base_fields') ?: [];
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
  public function getBaseFieldNames() {
    $default_base_fields = $this->get('default_base_fields') ?: [];
    $base_field_names = array_keys($default_base_fields);
    return array_combine($base_field_names, $base_field_names);
  }

}
