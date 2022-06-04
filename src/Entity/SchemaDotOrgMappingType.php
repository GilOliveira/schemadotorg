<?php

namespace Drupal\schemadotorg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
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
 *     "default_schema_types",
 *     "default_base_fields",
 *     "default_field_weights",
 *     "default_field_groups",
 *     "default_field_group_label_suffix",
 *     "default_field_group_form_type",
 *     "default_field_group_view_type",
 *     "recommended_schema_types",
 *     "default_schema_type_properties",
 *     "default_schema_type_subtypes",
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
   * An associative array of Schema.org types that support subtyping.
   *
   * @var array
   */
  protected $default_schema_type_subtypes = [];

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
   * An associative array of default field groups for Schema.org properties.
   *
   * @var array
   */
  protected $default_field_groups = [];

  /**
   * Default field group label suffix.
   *
   * @var string
   */
  protected $default_field_group_label_suffix = '';

  /**
   * Default field group form type.
   *
   * @var string
   */
  protected $default_field_group_form_type = '';

  /**
   * Default field group view type.
   *
   * @var string
   */
  protected $default_field_group_view_type = '';

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
    $default_properties = [];

    // Get default Schema.org type properties.
    $type_properties = $this->get('default_schema_type_properties');
    if ($type_properties) {
      /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager */
      $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
      $breadcrumbs = $schema_type_manager->getTypeBreadcrumbs($schema_type);
      foreach ($breadcrumbs as $breadcrumb) {
        foreach ($breadcrumb as $breadcrumb_type) {
          $breadcrumb_type_properties = $type_properties[$breadcrumb_type] ?? NULL;
          if ($breadcrumb_type_properties) {
            $default_properties += array_combine($breadcrumb_type_properties, $breadcrumb_type_properties);
          }
        }
      }
    }

    ksort($default_properties);
    return $default_properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchemaTypeSubtypes() {
    return $this->get('default_schema_type_subtypes');
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
  public function getDefaultFieldGroups() {
    return $this->get('default_field_groups');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldGroupLabelSuffix() {
    return $this->get('default_field_group_label_suffix');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldGroupFormatType(EntityDisplayInterface $display) {
    $display_type = ($display instanceof EntityFormDisplayInterface) ? 'form' : 'view';
    return $this->get('default_field_group_' . $display_type . '_type') ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldGroupFormatSettings(EntityDisplayInterface $display) {
    $type = $this->getDefaultFieldGroupFormatType($display);
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
