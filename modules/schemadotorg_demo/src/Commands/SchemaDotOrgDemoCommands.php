<?php

namespace Drupal\schemadotorg_demo\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Schema.org Demo Drush commands.
 */
class SchemaDotOrgDemoCommands extends DrushCommands {
  use StringTranslationTrait;

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
   * SchemaDotOrgDemoCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface $schema_api
   *   The Schema.org UI API.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, SchemaDotOrgUiApiInterface $schema_api, DevelGeneratePluginManager $devel_generate_manager = NULL) {
    parent::__construct();
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaApi = $schema_api;
    $this->develGenerateManager = $devel_generate_manager;
  }

  /* ************************************************************************ */
  // Setup.
  /* ************************************************************************ */

  /**
   * Allow users to choose the demo to be setup.
   *
   * @hook interact schemadotorg:demo-setup
   */
  public function setupInteract(InputInterface $input) {
    $this->interactChooseDemo($input, dt('setup'));
  }

  /**
   * Validates the Schema.org demo setup.
   *
   * @hook validate schemadotorg:demo-setup
   */
  public function setupValidate(CommandData $commandData) {
    $this->validateDemoCommand($commandData);
  }

  /**
   * Setup the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   *
   * @command schemadotorg:demo-setup
   *
   * @usage drush schemadotorg:demo-setup common
   *
   * @aliases sods
   */
  public function setup($name) {
    $types = $this->confirmDemoCommand($name, $this->t('setup'));

    $types = array_combine($types, $types);
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);

      $existing_mapping = $this->loadMappingByType($entity_type, $schema_type);
      if ($existing_mapping) {
        $t_args = ['@type' => $type];
        $this->io()->writeln($this->t("Schema.org type '@type' already exists.", $t_args));
        unset($types[$type]);
      }
      else {
        $this->schemaApi->createType($entity_type, $schema_type);
      }
    }

    if ($types) {
      $t_args = ['@types' => implode(', ', $types)];
      $this->io()->writeln($this->t('Schema.org types (@types) created.', $t_args));

      // Repair.
      $site_alias = Drush::aliasManager()->getSelf();
      $site_process = Drush::drush($site_alias, 'schemadotorg:repair', [], ['yes' => TRUE]);
      $site_process->run();
    }
  }

  /* ************************************************************************ */
  // Generate.
  /* ************************************************************************ */

  /**
   * Allow users to choose the demo to generate.
   *
   * @hook interact schemadotorg:demo-generate
   */
  public function generateInteract(InputInterface $input) {
    $this->interactChooseDemo($input, dt('generate'));
  }

  /**
   * Validates the Schema.org demo generate.
   *
   * @hook validate schemadotorg:demo-generate
   */
  public function generateValidate(CommandData $commandData) {
    $this->validateDemoCommand($commandData);
  }

  /**
   * Generate the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   *
   * @command schemadotorg:demo-generate
   *
   * @usage drush schemadotorg:demo-generate common
   *
   * @aliases sodg
   */
  public function generate($name) {
    $types = $this->confirmDemoCommand($name, $this->t('generate'));

    // Generate 5 examples for each type.
    $this->develGenerate($types);
  }

  /* ************************************************************************ */
  // Kill.
  /* ************************************************************************ */

  /**
   * Allow users to choose the demo to kill.
   *
   * @hook interact schemadotorg:demo-kill
   */
  public function killInteract(InputInterface $input) {
    $this->interactChooseDemo($input, dt('kill'));
  }

  /**
   * Validates the Schema.org demo kill.
   *
   * @hook validate schemadotorg:demo-kill
   */
  public function killValidate(CommandData $commandData) {
    $this->validateDemoCommand($commandData);
  }

  /**
   * Kill the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   *
   * @command schemadotorg:demo-kill
   *
   * @usage drush schemadotorg:demo-kill common
   *
   * @aliases sodk
   */
  public function kill($name) {
    $types = $this->confirmDemoCommand($name, $this->t('kill'));

    // Kill all generated content.
    $this->develGenerate($types, 0);
  }

  /* ************************************************************************ */
  // Teardown.
  /* ************************************************************************ */

  /**
   * Allow users to choose the demo to teardown.
   *
   * @hook interact schemadotorg:demo-teardown
   */
  public function teardownInteract(InputInterface $input) {
    $this->interactChooseDemo($input, dt('teardown'));
  }

  /**
   * Validates the Schema.org demo teardown.
   *
   * @hook validate schemadotorg:demo-teardown
   */
  public function teardownvalidateDemoCommand(CommandData $commandData) {
    $this->validateDemoCommand($commandData);
  }

  /**
   * Teardown the Schema.org demo.
   *
   * @param string $name
   *   The name of demo.
   *
   * @command schemadotorg:demo-teardown
   *
   * @usage drush schemadotorg:demo-teardown common
   *
   * @aliases sodt
   */
  public function teardown($name) {
    $types = $this->confirmDemoCommand($name, $this->t('teardown'));

    // Kill all generated content.
    $this->develGenerate($types, 0);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface  $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');

    // Reverse types to prevent entity reference errors.
    $types = array_reverse($types);
    $types = array_combine($types, $types);
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);

      $mapping = $this->loadMappingByType($entity_type, $schema_type);
      if (!$mapping) {
        $t_args = ['@type' => $type];
        $this->io()->writeln($this->t("Schema.org type '@type' already removed.", $t_args));
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
      $this->io()->writeln($this->t('Schema.org types (@types) deleted.', $t_args));
    }
  }

  /* ************************************************************************ */
  // Command helper methods.
  /* ************************************************************************ */

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
   * Allow users to choose the demo.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The user input.
   * @param string $action
   *   The action.
   */
  protected function interactChooseDemo(InputInterface $input, $action) {
    $name = $input->getArgument('name');
    if (!$name) {
      $demos = $this->configFactory->get('schemadotorg_demo.settings')->get('demos');
      $demos = array_keys($demos);
      $choices = array_combine($demos, $demos);
      $choice = $this->io()->choice($this->t('Choose a demo to @action.', ['@action' => $action]), $choices);
      $input->setArgument('name', $choice);
    }
  }

  /**
   * Validates the Schema.org demo name.
   */
  protected function validateDemoCommand(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $name = $arguments['name'] ?? '';
    $demo = $this->configFactory->get('schemadotorg_demo.settings')->get("demos.$name");
    if (!$demo) {
      throw new \Exception($this->t("Demo '@name' not found.", ['@name' => $name]));
    }
  }

  /**
   * Convert Schema.org demo command action.
   *
   * @param string $name
   *   The demo name.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $action
   *   The demo action.
   *
   * @return array
   *   The demo types.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  protected function confirmDemoCommand($name, TranslatableMarkup $action) {
    $types = $this->configFactory->get('schemadotorg_demo.settings')
      ->get("demos.$name");

    // If executing setup, prepend required types.
    if ($action->getUntranslatedString() === 'setup') {
      $required = $this->configFactory->get('schemadotorg_demo.settings')
        ->get("demos.required");
      if ($required) {
        $types = array_merge($required, $types);
        $types = array_unique($types);
      }
    }

    $t_args = [
      '@action' => $action,
      '@name' => $name,
      '@types' => implode(', ', $types),
    ];
    if (!$this->io()->confirm($this->t("Are you sure you want to @action '@name' demo with these types (@types)?", $t_args))) {
      throw new UserAbortException();
    }

    return $types;
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
      // 'node' => ['content', ['add-type-label' => TRUE, 'skip-fields' => 'menu_link']],
      'media' => ['media'],
      'taxonomy_term' => ['terms'],
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
        $options += ['kill' => TRUE, 'bundles' => $bundle];

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

}
