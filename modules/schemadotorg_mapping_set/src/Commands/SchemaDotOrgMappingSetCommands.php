<?php

namespace Drupal\schemadotorg_mapping_set\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Schema.org mapping set Drush commands.
 */
class SchemaDotOrgMappingSetCommands extends DrushCommands {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Schema.org mapping set manager.
   *
   * @var \Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface
   */
  protected $schemaMappingSetManager;

  /**
   * SchemaDotOrgMappingSetCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface $schema_mapping_set_manager
   *   The Schema.org mapping set manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SchemaDotOrgMappingSetManagerInterface $schema_mapping_set_manager) {
    parent::__construct();
    $this->configFactory = $config_factory;
    $this->schemaMappingSetManager = $schema_mapping_set_manager;
  }

  /* ************************************************************************ */
  // Setup.
  /* ************************************************************************ */

  /**
   * Allow users to choose the mapping set to be setup.
   *
   * @hook interact schemadotorg:set-setup
   */
  public function setupInteract(InputInterface $input) {
    $this->interactChooseMappingSet($input, dt('setup'));
  }

  /**
   * Validates the Schema.org mapping set setup.
   *
   * @hook validate schemadotorg:set-setup
   */
  public function setupValidate(CommandData $commandData) {
    $this->validateMappingSet($commandData);
  }

  /**
   * Setup the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   *
   * @command schemadotorg:set-setup
   *
   * @usage drush schemadotorg:set-setup common
   *
   * @aliases soss
   */
  public function setup($name) {
    $this->confirmMappingSet($name, dt('setup'), TRUE);
    $messages = $this->schemaMappingSetManager->setup($name);
    foreach ($messages as $message) {
      $this->io()->writeln($message);
    }
  }

  /* ************************************************************************ */
  // Generate.
  /* ************************************************************************ */

  /**
   * Allow users to choose the mapping set to generate.
   *
   * @hook interact schemadotorg:set-generate
   */
  public function generateInteract(InputInterface $input) {
    $this->interactChooseMappingSet($input, dt('generate'));
  }

  /**
   * Validates the Schema.org mapping set generate.
   *
   * @hook validate schemadotorg:set-generate
   */
  public function generateValidate(CommandData $commandData) {
    $this->validateMappingSet($commandData);
  }

  /**
   * Generate the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   *
   * @command schemadotorg:set-generate
   *
   * @usage drush schemadotorg:set-generate common
   *
   * @aliases sosg
   */
  public function generate($name) {
    $this->confirmMappingSet($name, dt('generate'));
    $this->schemaMappingSetManager->generate($name);
  }

  /* ************************************************************************ */
  // Kill.
  /* ************************************************************************ */

  /**
   * Allow users to choose the mapping set to kill.
   *
   * @hook interact schemadotorg:set-kill
   */
  public function killInteract(InputInterface $input) {
    $this->interactChooseMappingSet($input, dt('kill'));
  }

  /**
   * Validates the Schema.org mapping set kill.
   *
   * @hook validate schemadotorg:set-kill
   */
  public function killValidate(CommandData $commandData) {
    $this->validateMappingSet($commandData);
  }

  /**
   * Kill the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   *
   * @command schemadotorg:set-kill
   *
   * @usage drush schemadotorg:set-kill common
   *
   * @aliases sosk
   */
  public function kill($name) {
    $this->confirmMappingSet($name, dt('kill'));
    $this->schemaMappingSetManager->kill($name);
  }

  /* ************************************************************************ */
  // Teardown.
  /* ************************************************************************ */

  /**
   * Allow users to choose the mapping set to teardown.
   *
   * @hook interact schemadotorg:set-teardown
   */
  public function teardownInteract(InputInterface $input) {
    $this->interactChooseMappingSet($input, dt('teardown'));
  }

  /**
   * Validates the Schema.org mapping set teardown.
   *
   * @hook validate schemadotorg:set-teardown
   */
  public function teardownValidate(CommandData $commandData) {
    $this->validateMappingSet($commandData);
  }

  /**
   * Teardown the Schema.org mapping set.
   *
   * @param string $name
   *   The name of mapping set.
   *
   * @command schemadotorg:set-teardown
   *
   * @usage drush schemadotorg:set-teardown common
   *
   * @aliases sost
   */
  public function teardown($name) {
    $this->confirmMappingSet($name, dt('teardown'));
    $messages = $this->schemaMappingSetManager->teardown($name);
    foreach ($messages as $message) {
      $this->io()->writeln($message);
    }
  }

  /* ************************************************************************ */
  // Command helper methods.
  /* ************************************************************************ */

  /**
   * Allow users to choose the mapping set.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The user input.
   * @param string $action
   *   The action.
   */
  protected function interactChooseMappingSet(InputInterface $input, $action) {
    $name = $input->getArgument('name');
    if (!$name) {
      $sets = $this->configFactory->get('schemadotorg_mapping_set.settings')->get('sets');
      $sets = array_keys($sets);
      $choices = array_combine($sets, $sets);
      $choice = $this->io()->choice(dt('Choose a Schema.org mapping set to @action', ['@action' => $action]), $choices);
      $input->setArgument('name', $choice);
    }
  }

  /**
   * Validates the Schema.org mapping set name.
   */
  protected function validateMappingSet(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $name = $arguments['name'] ?? '';
    $mapping_set = $this->configFactory->get('schemadotorg_mapping_set.settings')->get("sets.$name");
    if (!$mapping_set) {
      throw new \Exception(dt("Schema.org mapping set '@name' not found.", ['@name' => $name]));
    }
  }

  /**
   * Convert Schema.org mapping set command action.
   *
   * @param string $name
   *   The mapping set name.
   * @param string $action
   *   The mapping set action.
   * @param bool $required
   *   Include required types.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  protected function confirmMappingSet($name, $action, $required = FALSE) {
    $types = $this->schemaMappingSetManager->getTypes($name, $required);

    $t_args = [
      '@action' => $action,
      '@name' => $name,
      '@types' => implode(', ', $types),
    ];
    if (!$this->io()->confirm(dt("Are you sure you want to @action the '@name' mapping set with these types (@types)?", $t_args))) {
      throw new UserAbortException();
    }
  }

}
