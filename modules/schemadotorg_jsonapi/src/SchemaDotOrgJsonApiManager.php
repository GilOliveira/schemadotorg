<?php

namespace Drupal\schemadotorg_jsonapi;

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
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;

/**
 * Schema.org JSON:API manager.
 */
class SchemaDotOrgJsonApiManager implements SchemaDotOrgJsonApiManagerInterface {
  use StringTranslationTrait;

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
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * Constructs a SchemaDotOrgJsonApi object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RedirectDestinationInterface $redirect_destination,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $field_manager,
    SchemaDotOrgNamesInterface $schema_names
  ) {
    $this->configFactory = $config_factory;
    $this->redirectDestination = $redirect_destination;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->schemaNames = $schema_names;
  }

  /**
   * {@inheritdoc}
   */
  public function requirements($phase) {
    if ($phase !== 'runtime') {
      return [];
    }

    $schemadotorg_config = $this->configFactory->get('schemadotorg_jsonapi.settings');
    if ($schemadotorg_config->get('disable_requirements')) {
      return [];
    }

    $requirements = [];

    // Resources disabled by default.
    $default_disabled = $this->configFactory
      ->get('jsonapi_extras.settings')
      ->get('default_disabled');
    if ($default_disabled) {
      $requirements['schemadotorg_jsonapi_default_disabled'] = [
        'title' => $this->t('Schema.org Blueprints JSON:API'),
        'value' => $this->t('Resources disabled by default'),
        'severity' => REQUIREMENT_OK,
      ];
    }
    else {
      $options = ['query' => $this->redirectDestination->getAsArray()];
      $jsonapi_href = Url::fromRoute('jsonapi_extras.settings', [], $options)->toString();
      $schemadotorg_href = Url::fromRoute('schemadotorg_jsonapi.settings', [], $options)->toString();

      $requirements['schemadotorg_jsonapi_default_disabled'] = [
        'title' => $this->t('Schema.org Blueprints JSON:API'),
        'value' => $this->t('Resources enabled by default'),
        'description' => [
          '#markup' => $this->t('It is recommended that JSON:API resources are <a href=":href">disabled by default</a>.', [':href' => $jsonapi_href])
          . '<br/>'
          . $this->t('<a href=":href">Disable this warning</a>', [':href' => $schemadotorg_href]),
        ],
        'severity' => REQUIREMENT_WARNING,
      ];
    }

    // Required resources disabled.
    $resources = [
      'file--file' => $this->entityTypeManager->getStorage('file')->getEntityType()->getLabel(),
    ];
    $missing = [];
    foreach ($resources as $resource_id => $label) {
      if (!$this->getResourceConfigStorage()->load($resource_id)) {
        $t_args = ['@label' => $label, '@id' => $resource_id];
        $missing[] = $this->t('@label (@id)', $t_args);
      }
    }
    if ($missing) {
      $t_args = [
        ':href' => Url::fromRoute('entity.jsonapi_resource_config.collection')->toString(),
      ];
      $requirements['schemadotorg_jsonapi_resource_disabled'] = [
        'title' => $this->t('Schema.org Blueprints JSON:API'),
        'value' => $this->t('Required resources disabled'),
        'description' => [
          '#markup' => $this->t('It is recommended that the below <a href=":href">JSON:API resources</a> be enabled', $t_args),
          'resources' => [
            '#theme' => 'item_list',
            '#items' => $missing,
          ],
        ],
        'severity' => REQUIREMENT_WARNING,
      ];
    }

    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public function install() {
    $this->installTaxonomyResource('Thing');
    $this->installTaxonomyResource('Enumeration');
  }

  /**
   * Install Schema.org taxonomy term JSON:API resource configuration.
   *
   * @param string $type
   *   The taxonomy vocabulary id.
   */
  protected function installTaxonomyResource($type) {
    $bundle = 'schema_' . strtolower($type);
    $resource_id = 'taxonomy_term--' . $bundle;
    $resource_config = $this->getResourceConfigStorage()->load($resource_id);
    // Never adjust an existing JSON:API resource configuration.
    if ($resource_config) {
      return;
    }

    $entity_type = $this->entityTypeManager->getStorage('taxonomy_term')->getEntityType();

    $resource_fields = [];

    // Enable selected schema taxonomy fields.
    $enabled_field_names = [
      // Name is used for translations.
      'name' => 'name',
      // Status is used to hide a Schema.org type.
      'status' => 'status',
      // Use value instead of Schema.org type, because having a 'type'
      // property is invalid.
      'schema_type' => 'value',
    ];

    $field_names = $this->getAllFieldNames($entity_type, $bundle);
    foreach ($field_names as $field_name) {
      if (isset($enabled_field_names[$field_name])) {
        $resource_fields[$field_name] = [
          'disabled' => FALSE,
          'fieldName' => $field_name,
          'publicName' => $enabled_field_names[$field_name],
          'enhancer' => ['id' => ''],
        ];
      }
      else {
        $resource_fields[$field_name] = [
          'disabled' => !$this->isFieldEnabled($field_name),
          'fieldName' => $field_name,
          'publicName' => $field_name,
          'enhancer' => ['id' => ''],
        ];
      }
    }

    ksort($resource_fields);
    $this->getResourceConfigStorage()->create([
      'id' => $resource_id,
      'disabled' => FALSE,
      'path' => $type,
      'resourceType' => $type,
      'resourceFields' => $resource_fields,
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function insertMappingResourceConfig(SchemaDotOrgMappingInterface $mapping) {
    $resource_config = $this->loadResourceConfig($mapping);
    if ($resource_config) {
      return $this->updateMappingResourceConfig($mapping);
    }

    $resource_fields = [];

    $schema_properties = $mapping->getAllSchemaProperties();

    $field_names = $this->getAllFieldNamesForMapping($mapping);
    foreach ($field_names as $field_name) {
      if (isset($schema_properties[$field_name])) {
        $resource_fields[$field_name] = [
          'disabled' => FALSE,
          'fieldName' => $field_name,
          'publicName' => $schema_properties[$field_name],
          'enhancer' => ['id' => ''],
        ];
      }
      else {
        $resource_fields[$field_name] = [
          'disabled' => !$this->isFieldEnabled($field_name),
          'fieldName' => $field_name,
          'publicName' => $field_name,
          'enhancer' => ['id' => ''],
        ];
      }
    }

    $name = $this->getResourceConfigPath($mapping);
    ksort($resource_fields);
    $this->getResourceConfigStorage()->create([
      'id' => $this->getResourceId($mapping),
      'disabled' => FALSE,
      'path' => $name,
      'resourceType' => $name,
      'resourceFields' => $resource_fields,
    ])->save();
  }

  /**
   * Get JSON:API resource config path.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   A Schema.org mapping.
   *
   * @return string
   *   JSON:API resource config path.
   */
  protected function getResourceConfigPath(SchemaDotOrgMappingInterface $mapping) {
    // Get the entity type's resource path prefix used to prevent conflicts.
    // (i.e. ContentPerson, BlockContactPoint, UserPerson, etc...).
    $path_prefixes = $this->configFactory
      ->get('schemadotorg_jsonapi.settings')
      ->get('path_prefixes');
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $path_prefix = (isset($path_prefixes[$entity_type_id]))
      ? $path_prefixes[$entity_type_id]
      : $this->schemaNames->snakeCaseToUpperCamelCase($entity_type_id);

    if ($mapping->isTargetEntityTypeBundle()) {
      // Use the bundle machine name which could be more specific
      // (i.e. contact_point_phone => ContactPointPhone).
      $bundle = $this->schemaNames->snakeCaseToUpperCamelCase($mapping->getTargetBundle());
      return ($this->isExistingResourceConfigPath($bundle))
        ? $path_prefix . $bundle
        : $bundle;
    }
    else {
      // Use the Schema.org type.
      // (i.e. Person).
      $schema_type = $mapping->getSchemaType();
      return ($this->isExistingResourceConfigPath($schema_type))
        ? $path_prefix . $schema_type
        : $schema_type;
    }
  }

  /**
   * Determine if a JSON:API resource config path exists.
   *
   * @param string $path
   *   A JSON:API resource config path.
   *
   * @return bool
   *   TRUE if a JSON:API resource config path exists.
   */
  protected function isExistingResourceConfigPath($path) {
    return (boolean) $this->getResourceConfigStorage()->loadByProperties(['path' => $path]);
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

    $properties = $mapping->getAllSchemaProperties();
    foreach ($properties as $field_name => $property) {
      // Never update an existing resource field.
      // Ensures that an API field is never changed after it has been created.
      if (!isset($resource_fields[$field_name])) {
        $resource_fields[$field_name] = [
          'disabled' => FALSE,
          'fieldName' => $field_name,
          'publicName' => $property,
          'enhancer' => ['id' => ''],
        ];
      }
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
    $entity_type_id = $field->getTargetEntityTypeId();
    $bundle = $field->getTargetBundle();
    if ($entity_type_id === 'taxonomy_term' && $bundle === 'schema_enumeration') {
      $this->insertEnumerationFieldConfigResource($field);
    }
    else {
      $this->insertMappingFieldConfigResource($field);
    }
  }

  /**
   * Insert Schema.org enumeration field into JSON:API resource config.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field.
   */
  protected function insertEnumerationFieldConfigResource(FieldConfigInterface $field) {
    $resource_id = 'taxonomy_term--schema_enumeration';
    $resource_config = $this->getResourceConfigStorage()->load($resource_id);
    // In the JSON:API resource config does not exist for Enumeration,
    // we need to create it.
    if (!$resource_config) {
      return $this->installTaxonomyResource('Enumeration');
    }

    $field_name = $field->getName();

    // Never update an existing resource field.
    // Ensures that an API field is never changed after it has been created.
    $resource_fields = $resource_config->get('resourceFields');
    if (isset($resource_fields[$field_name])) {
      return;
    }

    $resource_fields[$field_name] = [
      'disabled' => !$this->isFieldEnabled($field_name),
      'fieldName' => $field_name,
      'publicName' => $field_name,
      'enhancer' => ['id' => ''],
    ];

    ksort($resource_fields);
    $resource_config
      ->set('resourceFields', $resource_fields)
      ->save();
  }

  /**
   * Insert Schema.org property/field into JSON:API resource config.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field.
   */
  protected function insertMappingFieldConfigResource(FieldConfigInterface $field) {
    // Do not insert field into JSON:API resource config if the
    // Scheme.org entity type builder is adding it.
    // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::addFieldToEntity
    if (!empty($field->schemaDotOrgAddFieldToEntity)) {
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

    $property = $mapping->getSchemaPropertyMapping($field_name);
    if ($property) {
      $resource_fields[$field_name] = [
        'disabled' => FALSE,
        'fieldName' => $field_name,
        'publicName' => $property,
        'enhancer' => ['id' => ''],
      ];
    }
    else {
      $resource_fields[$field_name] = [
        'disabled' => !$this->isFieldEnabled($field_name),
        'fieldName' => $field_name,
        'publicName' => $field_name,
        'enhancer' => ['id' => ''],
      ];
    }

    ksort($resource_fields);
    $resource_config
      ->set('resourceFields', $resource_fields)
      ->save();
  }

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
   *   A Schema.org mapping.
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

  /**
   * Get JSON:API resource config id for a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   A Schema.org mapping.
   *
   * @return string
   *   A JSON:API resource config id for a Schema.org mapping.
   */
  protected function getResourceId(SchemaDotOrgMappingInterface $mapping) {
    $target_entity_type_id = $mapping->getTargetEntityTypeId();
    $target_bundle = $mapping->getTargetBundle();
    return $target_entity_type_id . '--' . $target_bundle;
  }

  /**
   * Determine is a field enabled.
   *
   * @param string $field_name
   *   A field name.
   *
   * @return bool
   *   TRUE if a field enabled.
   */
  protected function isFieldEnabled($field_name) {
    $default_enabled_fields = $this->configFactory
      ->get('schemadotorg_jsonapi.settings')
      ->get('default_enabled_fields');
    return in_array($field_name, $default_enabled_fields);
  }

  /**
   * Gets all field names for a Schemam.org mapping entity type and bundle.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   A Schema.org mapping.
   *
   * @return string[]
   *   All field names.
   */
  protected function getAllFieldNamesForMapping(SchemaDotOrgMappingInterface $mapping) {
    $entity_type = $mapping->getTargetEntityTypeDefinition();
    $bundle = $mapping->getTargetBundle();
    return $this->getAllFieldNames($entity_type, $bundle);
  }

  /**
   * Gets all field names for a given entity type and bundle.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which to get all field names.
   * @param string $bundle
   *   The bundle for which to get all field names.
   *
   * @todo This is a copy of ResourceTypeRepository::getAllFieldNames. We can't
   * reuse that code because it's protected.
   *
   * @return string[]
   *   All field names.
   */
  protected function getAllFieldNames(EntityTypeInterface $entity_type, $bundle) {
    if (is_a($entity_type->getClass(), FieldableEntityInterface::class, TRUE)) {
      $field_definitions = $this->fieldManager->getFieldDefinitions(
        $entity_type->id(),
        $bundle
      );
      return array_keys($field_definitions);
    }
    elseif (is_a($entity_type->getClass(), ConfigEntityInterface::class, TRUE)) {
      // @todo Uncomment the first line, remove everything else once https://www.drupal.org/project/drupal/issues/2483407 lands.
      // return array_keys($entity_type->getPropertiesToExport());
      $export_properties = $entity_type->getPropertiesToExport();
      if ($export_properties !== NULL) {
        return array_keys($export_properties);
      }
      else {
        return ['id', 'type', 'uuid', '_core'];
      }
    }

    return [];
  }

}
