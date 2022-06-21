<?php

namespace Drupal\schemadotorg_demo\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\schemadotorg_demo\SchemaDotOrgDemoManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Schema.org Demo Drush commands.
 */
class SchemaDotOrgDemoCommands extends DrushCommands {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Schema.org demo manager.
   *
   * @var \Drupal\schemadotorg_demo\SchemaDotOrgDemoManagerInterface
   */
  protected $schemaDemoManager;

  /**
   * SchemaDotOrgDemoCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\schemadotorg_demo\SchemaDotOrgDemoManagerInterface $schema_demo_manager
   *   The Schema.org demo manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SchemaDotOrgDemoManagerInterface $schema_demo_manager) {
    parent::__construct();
    $this->configFactory = $config_factory;
    $this->schemaDemoManager = $schema_demo_manager;
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
    $this->confirmDemoCommand($name, dt('setup'), TRUE);
    $messages = $this->schemaDemoManager->setup($name);
    foreach ($messages as $message) {
      $this->io()->writeln($message);
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
    $this->confirmDemoCommand($name, dt('generate'));
    $this->schemaDemoManager->generate($name);
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
    $this->confirmDemoCommand($name, dt('kill'));
    $this->schemaDemoManager->kill($name);
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
    $this->confirmDemoCommand($name, dt('teardown'));
    $messages = $this->schemaDemoManager->teardown($name);
    foreach ($messages as $message) {
      $this->io()->writeln($message);
    }
  }

  /* ************************************************************************ */
  // Command helper methods.
  /* ************************************************************************ */

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
      $choice = $this->io()->choice(dt('Choose a demo to @action.', ['@action' => $action]), $choices);
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
      throw new \Exception(dt("Demo '@name' not found.", ['@name' => $name]));
    }
  }

  /**
   * Convert Schema.org demo command action.
   *
   * @param string $name
   *   The demo name.
   * @param string $action
   *   The demo action.
   * @param bool $required
   *   Include required types.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  protected function confirmDemoCommand($name, $action, $required = FALSE) {
    $types = $this->schemaDemoManager->getTypes($name, $required);

    $t_args = [
      '@action' => $action,
      '@name' => $name,
      '@types' => implode(', ', $types),
    ];
    if (!$this->io()->confirm(dt("Are you sure you want to @action '@name' demo with these types (@types)?", $t_args))) {
      throw new UserAbortException();
    }
  }

}
