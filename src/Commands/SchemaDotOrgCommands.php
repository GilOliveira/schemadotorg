<?php

namespace Drupal\schemadotorg\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgInstallerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Drush commands.
 */
class SchemaDotOrgCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * The Schema.org installer service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface
   */
  protected $schemaInstaller;

  /**
   * The Schema.org config manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface
   */
  protected $schemaConfigManager;

  /**
   * The Schema.org entity relationship manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface
   */
  protected $schemaEntityRelationshipManager;

  /**
   * SchemaDotOrgCommands constructor.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $schema_installer
   *   The Schema.org installer service.
   * @param \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface $schema_config_manager
   *   The Schema.org schema config manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface $schema_entity_relationship_manager
   *   The Schema.org schema entity relationship manager.
   */
  public function __construct(
    SchemaDotOrgInstallerInterface $schema_installer,
    SchemaDotOrgConfigManagerInterface $schema_config_manager,
    SchemaDotOrgEntityRelationshipManagerInterface $schema_entity_relationship_manager
  ) {
    parent::__construct();
    $this->schemaInstaller = $schema_installer;
    $this->schemaConfigManager = $schema_config_manager;
    $this->schemaEntityRelationshipManager = $schema_entity_relationship_manager;
  }

  /**
   * Update Schema.org data.
   *
   * @command schemadotorg:update-schema
   *
   * @usage schemadotorg:update-schema
   *
   * @aliases soup
   */
  public function update() {
    if (!$this->io()->confirm($this->t('Are you sure you want to update Schema.org data?'))) {
      throw new UserAbortException();
    }

    $this->schemaInstaller->install();
    $this->output()->writeln($this->t('Schema.org data.'));
  }

  /**
   * Update Schema.org repair.
   *
   * @command schemadotorg:repair
   *
   * @usage schemadotorg:repair
   *
   * @aliases sorp
   *
   * @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportMappingsController::relationships
   */
  public function repair() {
    if (!$this->io()->confirm($this->t('Are you sure you want to repair Schema.org configuration and relationships?'))) {
      throw new UserAbortException();
    }

    // Configuration.
    $this->schemaConfigManager->repair();

    // Relationships.
    $messages = $this->schemaEntityRelationshipManager->repair();
    foreach ($messages as $message) {
      $this->io->success($message);
    }
  }

}
