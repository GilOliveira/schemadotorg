<?php

namespace Drupal\schemadotorg_jsonapi;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;

/**
 * Schema.org JSON:API manager.
 */
class SchemaDotOrgJsonApiManager implements SchemaDotOrgJsonApiManagerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

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
   * The JSON:API configurable resource type repository.
   *
   * @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * Constructs a SchemaDotOrgJsonApiManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository $resource_type_respository
   *   The JSON:API configurable resource type repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RedirectDestinationInterface $redirect_destination,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $field_manager,
    ConfigurableResourceTypeRepository $resource_type_respository,
    SchemaDotOrgNamesInterface $schema_names
  ) {
    $this->configFactory = $config_factory;
    $this->redirectDestination = $redirect_destination;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->resourceTypeRepository = $resource_type_respository;
    $this->schemaNames = $schema_names;
  }

  /* ************************************************************************ */
  // Resource includes methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function getResourceIncludes(ResourceType $resource_type) {
    return $this->getResourceIncludesRecursive($resource_type);
  }

  /**
   * Get resource type's entity reference fields as an array of includes.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type.
   * @param int $level
   *   The level of includes.
   *
   * @return array
   *   An array of entity reference field public names to be used as includes.
   */
  protected function getResourceIncludesRecursive(ResourceType $resource_type, $level = 0) {
    $entity_type_id = $resource_type->getEntityTypeId();
    $bundle = $resource_type->getBundle();

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping')
      ->load("$entity_type_id.$bundle");
    if (!$mapping) {
      return [];
    }

    $includes = [];

    $relationships = $resource_type->getRelatableResourceTypes();
    $field_names = array_keys($mapping->getSchemaProperties());
    $field_definitions = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($field_names as $field_name) {
      $field = $resource_type->getFieldByInternalName($field_name);
      if (!$field) {
        continue;
      }

      $public_name = $field->getPublicName();
      if (!isset($relationships[$public_name])) {
        continue;
      }

      // Append field's public name to includes.
      $includes[$public_name] = $public_name;

      // Get nested includes for entity references.
      // @todo Determine how many include levels should be returned.
      if ($level < 1) {
        $field_type = $field_definitions[$field_name]->getType();
        if (in_array($field_type, ['entity_reference', 'entity_reference_revisions'])) {
          $settings = $field_definitions[$field_name]->getSettings();
          $target_type = $settings['target_type'];
          $target_bundles = NestedArray::getValue($settings, ['handler_settings', 'target_bundles']) ?? [];
          foreach ($target_bundles as $target_bundle) {
            $target_resource_id = "$target_type--$target_bundle";
            $target_resource_type = $this->resourceTypeRepository->getByTypeName($target_resource_id);
            $target_includes = $this->getResourceIncludesRecursive($target_resource_type, $level + 1);
            foreach ($target_includes as $target_include) {
              // Append target bundle's field's public name to includes.
              $includes["$public_name.$target_include"] = "$public_name.$target_include";
            }
          }
        }
      }
    }

    return $includes;
  }

  /* ************************************************************************ */
  // Schema.org mapping insert and update resource methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function insertMappingResourceConfig(SchemaDotOrgMappingInterface $mapping) {
    $resource_config = $this->loadResourceConfig($mapping);
    if ($resource_config) {
      return $this->updateMappingResourceConfig($mapping);
    }

    $resource_fields = [];

    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();
    $field_definitions = $this->fieldManager->getFieldDefinitions(
      $entity_type_id,
      $bundle
    );
    $field_names = array_keys($field_definitions);
    foreach ($field_names as $field_name) {
      $resource_fields[$field_name] = [
        'fieldName' => $field_name,
        'publicName' => $this->getResourceFieldPublicName($mapping, $field_name),
        'disabled' => $this->isResourceFieldDisabled($mapping, $field_name),
        'enhancer' => ['id' => ''],
      ];
    }

    ksort($resource_fields);
    $this->getResourceConfigStorage()->create([
      'id' => $this->getResourceId($mapping),
      'path' => $this->getResourcePath($mapping),
      'resourceType' => $this->getResourceType($mapping),
      'resourceFields' => $resource_fields,
      'disabled' => FALSE,
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function updateMappingResourceConfig(SchemaDotOrgMappingInterface $mapping) {
    $resource_config = $this->loadResourceConfig($mapping);
    if (!$resource_config) {
      return $this->insertMappingResourceConfig($mapping);
    }

    $resource_fields = $resource_config->get('resourceFields');

    $properties = $mapping->getSchemaProperties();
    foreach ($properties as $field_name => $property) {
      // Never update an existing resource field.
      // Ensures that an API field is never changed after it has been created.
      if (isset($resource_fields[$field_name])) {
        continue;
      }

      $resource_fields[$field_name] = [
        'disabled' => $this->isResourceFieldDisabled($mapping, $field_name),
        'fieldName' => $field_name,
        'publicName' => $this->getResourceFieldPublicName($mapping, $field_name),
        'enhancer' => ['id' => ''],
      ];
    }

    ksort($resource_fields);
    $resource_config
      ->set('resourceFields', $resource_fields)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function insertFieldConfigResource(FieldConfigInterface $field) {
    // Do not insert field into JSON:API resource config if the
    // Scheme.org entity type builder is adding it.
    // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::addFieldToEntity
    if (!empty($field->schemaDotOrgType) && !empty($field->schemaDotOrgProperty)) {
      return;
    }

    $entity_type_id = $field->getTargetEntityTypeId();
    $bundle = $field->getTargetBundle();
    $field_name = $field->getName();

    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $mapping_storage->load("$entity_type_id.$bundle");
    if (!$mapping) {
      return;
    }

    $resource_config = $this->loadResourceConfig($mapping);
    if (!$resource_config) {
      return;
    }

    // Never update an existing resource field.
    // Ensures that an API field is never changed after it has been created.
    $resource_fields = $resource_config->get('resourceFields');
    if (isset($resource_fields[$field_name])) {
      return;
    }

    $resource_fields[$field_name] = [
      'disabled' => $this->isResourceFieldDisabled($mapping, $field_name),
      'fieldName' => $field_name,
      'publicName' => $this->getResourceFieldPublicName($mapping, $field_name),
      'enhancer' => ['id' => ''],
    ];

    ksort($resource_fields);
    $resource_config
      ->set('resourceFields', $resource_fields)
      ->save();
  }

  /* ************************************************************************ */
  // Schema.org resource storage methods.
  /* ************************************************************************ */

  /**
   * Get JSON:API resource config storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   JSON:API resource config storage.
   */
  protected function getResourceConfigStorage() {
    return $this->entityTypeManager->getStorage('jsonapi_resource_config');
  }

  /**
   * Load JSON:API resource config id for a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   *
   * @return \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig|null
   *   A JSON:API resource config id.
   */
  protected function loadResourceConfig(SchemaDotOrgMappingInterface $mapping) {
    $target_entity_type_id = $mapping->getTargetEntityTypeId();
    $target_bundle = $mapping->getTargetBundle();
    $resource_id = $target_entity_type_id . '--' . $target_bundle;
    return $this->getResourceConfigStorage()->load($resource_id);
  }

  /* ************************************************************************ */
  // Schema.org resource property methods.
  /* ************************************************************************ */

  /**
   * Get JSON:API resource id.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   *
   * @return string
   *   A JSON:API resource id.
   */
  protected function getResourceId(SchemaDotOrgMappingInterface $mapping) {
    $target_entity_type_id = $mapping->getTargetEntityTypeId();
    $target_bundle = $mapping->getTargetBundle();
    return $target_entity_type_id . '--' . $target_bundle;
  }

  /**
   * Get JSON:API resource type.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   * @param string $delimiter
   *   The delimiter used to separate the entity type from the bundle.
   *
   * @return string
   *   JSON:API resource type.
   */
  protected function getResourceType(SchemaDotOrgMappingInterface $mapping, $delimiter = '--') {
    $resource_type_schemadotorg = $this->configFactory
      ->get('schemadotorg_jsonapi.settings')
      ->get('resource_type_schemadotorg');

    $target_entity_type_id = $mapping->getTargetEntityTypeId();
    if ($resource_type_schemadotorg) {
      $schema_type = $mapping->getSchemaType();
      return $target_entity_type_id . $delimiter . $this->schemaNames->camelCaseToSnakeCase($schema_type);
    }
    else {
      $target_bundle = $mapping->getTargetBundle();
      return $target_entity_type_id . $delimiter . $target_bundle;
    }
  }

  /**
   * Get JSON:API resource path.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   *
   * @return string
   *   JSON:API resource path.
   */
  protected function getResourcePath(SchemaDotOrgMappingInterface $mapping) {
    return $this->getResourceType($mapping, '/');
  }

  /**
   * Get a resource field's public name.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The resource field's public name,
   */
  protected function getResourceFieldPublicName(SchemaDotOrgMappingInterface $mapping, $field_name) {
    // Make sure use Schema.org property as the resource's field name is enabled.
    $resource_field_schemadotorg = $this->configFactory
      ->get('schemadotorg_jsonapi.settings')
      ->get('resource_field_schemadotorg');
    if (!$resource_field_schemadotorg) {
      return $field_name;
    }

    // Never alter base field name, even if they are mapped to a
    // Schema.org properties because the can break front-end expectation.
    // (i.e. langcode => inLanguage)
    $entity_type_id = $mapping->getTargetEntityTypeId();
    if ($this->isBaseField($entity_type_id, $field_name)) {
      return $field_name;
    }

    $property = $mapping->getSchemaPropertyMapping($field_name);
    return ($property) ? $this->schemaNames->camelCaseToSnakeCase($property) : $field_name;
  }

  /**
   * Determine if a resource field is disabled.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE if a resource field is disabled.
   */
  protected function isResourceFieldDisabled(SchemaDotOrgMappingInterface $mapping, $field_name) {
    if ($mapping->getSchemaPropertyMapping($field_name)) {
      return FALSE;
    }

    $default_base_fields = $this->configFactory
      ->get('schemadotorg_jsonapi.settings')
      ->get('default_base_fields');
    if (empty($default_base_fields)) {
      return FALSE;
    }

    return !in_array($field_name, $default_base_fields);
  }

  /* ************************************************************************ */
  // Field helper methods.
  /* ************************************************************************ */

  /**
   * Determine if a field is a base field.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE if the field is a base field.
   */
  protected function isBaseField($entity_type_id, $field_name) {
    $field_base_definitions = $this->fieldManager->getBaseFieldDefinitions($entity_type_id);
    return isset($field_base_definitions[$field_name]);
  }

}
