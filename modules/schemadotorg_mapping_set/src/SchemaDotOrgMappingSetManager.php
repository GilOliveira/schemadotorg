<?php

namespace Drupal\schemadotorg_mapping_set;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface;
use Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface;

/**
 * Schema.org mapping set manager.
 */
class SchemaDotOrgMappingSetManager implements SchemaDotOrgMappingSetManagerInterface {
  use StringTranslationTrait;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * The Schema.org entity relationship manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface
   */
  protected $schemaEntityRelationshipManager;

  /**
   * The Schema.org UI API.
   *
   * @var \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface
   */
  protected $schemaApi;

  /**
   * The devel generate plugin manager.
   *
   * @var \Drupal\devel_generate\DevelGeneratePluginManager|null
   */
  protected $develGenerateManager;

  /**
   * SchemaDotOrgMappingSetCommands constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface $schema_entity_relationship_manager
   *   The Schema.org schema entity relationship manager.
   * @param \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface $schema_api
   *   The Schema.org UI API.
   * @param \Drupal\devel_generate\DevelGeneratePluginManager|null $devel_generate_manager
   *   The Devel generate manager.
   */
  public function __construct(
    StateInterface $state,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgEntityRelationshipManagerInterface $schema_entity_relationship_manager,
    SchemaDotOrgUiApiInterface $schema_api,
    DevelGeneratePluginManager $devel_generate_manager = NULL
  ) {
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaEntityRelationshipManager = $schema_entity_relationship_manager;
    $this->schemaApi = $schema_api;
    $this->develGenerateManager = $devel_generate_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setup($name) {
    if ($this->isSetup($name)) {
      return [$this->t('Schema.org mapping set @name is already setup.', ['@name' => $name])];
    }

    // Setup required.
    if ($name !== 'required'
      && !$this->isSetup('required')
      && $this->getTypes('required')) {
      $this->setup('required');
    }

    $messages = [];

    $types = $this->getTypes($name);
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);

      $existing_mapping = $this->loadMappingByType($entity_type, $schema_type);
      if ($existing_mapping) {
        $t_args = ['@type' => $type];
        $messages[] = $this->t("Schema.org type '@type' already exists.", $t_args);
        unset($types[$type]);
      }
      else {
        $this->schemaApi->createType($entity_type, $schema_type);
      }
    }

    if ($types) {
      // Display message.
      $t_args = ['@types' => implode(', ', $types)];
      $messages[] = $this->t('Schema.org types (@types) created.', $t_args);

      // Repair.
      $this->schemaEntityRelationshipManager->repair();
    }

    // Set that the mapping set was set up.
    $setup = $this->state->get('schemadotorg_mapping_set_setup') ?? [];
    $setup[$name] = $name;
    $this->state->set('schemadotorg_mapping_set_setup', $setup);

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function teardown($name) {
    if (!$this->isSetup($name)) {
      return [$this->t('Schema.org mapping set $name is not setup.')];
    }

    $this->kill($name);

    $messages = [];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface  $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');

    // Reverse types to prevent entity reference errors.
    $types = $this->getTypes($name);
    $types = array_reverse($types, TRUE);
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);

      $mapping = $this->loadMappingByType($entity_type, $schema_type);
      if (!$mapping) {
        $t_args = ['@type' => $type];
        $messages[] = $this->t("Schema.org type '@type' already removed.", $t_args);
        unset($types[$type]);
        continue;
      }

      // Determine if the entity type bundle is default entity type that should
      // not be deleted.
      // (i.e. node:article, node:page, taxonomy_term:tags, etc...)
      $target_entity_id = $mapping->getTargetEntityTypeId();
      $target_entity_bundle = $mapping->getTargetEntityBundleEntity();
      $mapping_type = $mapping_type_storage->load($target_entity_id);
      $default_bundles = $mapping_type->getDefaultSchemaTypeBundles($schema_type);
      $is_default_bundle = isset($default_bundles[$target_entity_bundle->id()]);

      if ($is_default_bundle) {
        $options = ['delete-fields' => TRUE];
      }
      else {
        $options = ['delete-entity' => TRUE];
      }

      $this->schemaApi->deleteType($entity_type, $schema_type, $options);
    }

    if ($types) {
      $t_args = ['@type' => implode(', ', $types)];
      $messages[] = $this->t('Schema.org types (@types) deleted.', $t_args);
    }

    // Unset that the mapping set was set up.
    $setup = $this->state->get('schemadotorg_mapping_set_setup') ?? [];
    unset($setup[$name]);
    $this->state->set('schemadotorg_mapping_set_setup', $setup);

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function generate($name) {
    $types = $this->getTypes($name, TRUE);
    $this->develGenerate($types);
  }

  /**
   * {@inheritdoc}
   */
  public function kill($name) {
    $types = $this->getTypes($name, TRUE);
    $this->develGenerate($types, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function isSetup($name) {
    $setup = $this->state->get('schemadotorg_mapping_set_setup') ?? [];
    return isset($setup[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes($name, $required = FALSE) {
    $mapping_set = $this->configFactory
      ->get('schemadotorg_mapping_set.settings')
      ->get("sets.$name");
    if (empty($mapping_set)) {
      return [];
    }

    $types = array_combine($mapping_set['types'], $mapping_set['types']);

    // Prepend required types.
    if ($required) {
      $types = $this->getTypes('required') + $types;
    }

    return $types;
  }

  /**
   * Get entity type bundles.
   *
   * @param array $types
   *   An array of entity and Schema.org types.
   *
   * @return array
   *   An array entity type bundles.
   */
  protected function getEntityTypeBundles(array $types) {
    // Collect the entity type and bundles to be generated.
    $entity_types = [];
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);
      $entity_types += [$entity_type => []];
      $existing_mapping = $this->loadMappingByType($entity_type, $schema_type);
      if ($existing_mapping) {
        $target_bundle = $existing_mapping->getTargetBundle();
        $entity_types[$entity_type][$target_bundle] = $target_bundle;
      }
    }
    return array_filter($entity_types);
  }

  /**
   * Load Schema.org mapping by entity and Schema.org type.
   *
   * @param string $entity_type
   *   The entity type id.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   A Schema.org mapping.
   */
  protected function loadMappingByType($entity_type, $schema_type) {
    $mappings = $this->entityTypeManager->getStorage('schemadotorg_mapping')->loadByProperties([
      'target_entity_type_id' => $entity_type,
      'type' => $schema_type,
    ]);
    return $mappings ? reset($mappings) : NULL;
  }

  /**
   * Execute devel generate command.
   *
   * @param array $types
   *   An array of entity and Schema.org types.
   * @param int $num
   *   The number of entities to create for each type.
   */
  protected function develGenerate(array $types, $num = 5) {
    // Make sure the devel generate manager and module are installed.
    if (!$this->develGenerateManager) {
      throw new \Exception('The devel_generate.module needs to be enabled.');
    }

    // Collect the entity type and bundles to be generated.
    $entity_types = $this->getEntityTypeBundles($types);

    // Mapping entity type to devel-generate command with default options.
    $commands = [
      'user' => ['users'],
      'node' => ['content', ['add-type-label' => TRUE]],
      'media' => ['media'],
      'taxonomy_term' => ['term'],
    ];
    foreach ($entity_types as $entity_type => $bundles) {
      if (!isset($commands[$entity_type])) {
        continue;
      }

      $devel_generate_plugin_id = $commands[$entity_type][0];
      foreach ($bundles as $bundle) {
        // Args.
        $args = [(string) $num];
        // Options.
        $options = $commands[$entity_type][1] ?? [];
        $options += ['kill' => TRUE, 'bundles' => $bundle, 'languages' => NULL, 'translations' => NULL];

        // Plugin.
        /** @var \Drupal\devel_generate\DevelGenerateBaseInterface $devel_generate_plugin */
        $devel_generate_plugin = $this->develGenerateManager->createInstance($devel_generate_plugin_id);
        // Parameters.
        $parameters = $devel_generate_plugin->validateDrushParams($args, $options);
        // Generate.
        $devel_generate_plugin->generate($parameters);
      }
    }
  }

}