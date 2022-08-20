<?php

namespace Drupal\schemadotorg\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgInstallerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Drush commands.
 */
class SchemaDotOrgCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The Schema.org mapping manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $schemaMappingManager;

  /**
   * SchemaDotOrgCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $schema_installer
   *   The Schema.org installer service.
   * @param \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface $schema_config_manager
   *   The Schema.org schema config manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityRelationshipManagerInterface $schema_entity_relationship_manager
   *   The Schema.org schema entity relationship manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $schema_mapping_manager
   *   The Schema.org mapping manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgInstallerInterface $schema_installer,
    SchemaDotOrgConfigManagerInterface $schema_config_manager,
    SchemaDotOrgEntityRelationshipManagerInterface $schema_entity_relationship_manager,
    SchemaDotOrgMappingManagerInterface $schema_mapping_manager
  ) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaInstaller = $schema_installer;
    $this->schemaConfigManager = $schema_config_manager;
    $this->schemaEntityRelationshipManager = $schema_entity_relationship_manager;
    $this->schemaMappingManager = $schema_mapping_manager;
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
    if (!$this->io()->confirm(dt('Are you sure you want to update Schema.org data?'))) {
      throw new UserAbortException();
    }

    $this->schemaInstaller->install();
    $this->output()->writeln(dt('Schema.org data.'));
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
    if (!$this->io()->confirm(dt('Are you sure you want to repair Schema.org configuration and relationships?'))) {
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

  /* ************************************************************************ */
  // Create type.
  /* ************************************************************************ */

  /**
   * Validates the entity type and Schema.org type to be created.
   *
   * @hook validate schemadotorg:create-type
   */
  public function createTypeValidate(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $types = $arguments['types'] ?? [];
    if (empty($types)) {
      throw new \Exception(dt('Schema.org types are required.'));
    }

    foreach ($types as $type) {
      // Validate mapping type.
      if (strpos($type, ':') === FALSE) {
        $t_args = ['@type' => $type];
        $message = dt("The Schema.org mapping type '@type' is not valid. A Schema.org type must be defined with an entity type and Schema.org type delimited using a colon (:).", $t_args);
        throw new \Exception($message);
      }

      [$entity_type_id, $schema_type] = explode(':', $type);
      $this->schemaMappingManager->createTypeValidate($entity_type_id, $schema_type);
    }
  }

  /**
   * Create Schema.org types.
   *
   * @param array $types
   *   A list of Schema.org mapping types.
   *
   * @command schemadotorg:create-type
   *
   * @usage drush schemadotorg:create-type paragraph:ContactPoint paragraph:PostalAddress
   * @usage drush schemadotorg:create-type media:AudioObject media:DataDownload media:ImageObject media:VideoObject
   * @usage drush schemadotorg:create-type user:Person
   * @usage drush schemadotorg:create-type node:Person node:Organization node:Place node:Event node:CreativeWork
   * @usage drush schemadotorg:create-type node:Place
   * @usage drush schemadotorg:create-type node:Organization
   *
   * @aliases socr
   */
  public function createType(array $types) {
    $t_args = ['@types' => implode(', ', $types)];
    if (!$this->io()->confirm(dt('Are you sure you want to create these types (@types)?', $t_args))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    $types = array_combine($types, $types);
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);
      $existing_mapping = $mapping_storage->loadByProperties([
        'target_entity_type_id' => $entity_type,
        'schema_type' => $schema_type,
      ]);
      if ($existing_mapping) {
        $t_args = ['@type' => $type];
        $this->io()->writeln(dt("Schema.org type '@type' already exists.", $t_args));
        unset($types[$type]);
      }
      else {
        $this->schemaMappingManager->createType($entity_type, $schema_type);
      }
    }

    if ($types) {
      $t_args = ['@types' => implode(', ', $types)];
      $this->io()->writeln(dt('Schema.org types (@types) created.', $t_args));
    }
  }

  /* ************************************************************************ */
  // Delete type.
  /* ************************************************************************ */

  /**
   * Validates the entity type and Schema.org type to be deleted.
   *
   * @hook validate schemadotorg:delete-type
   */
  public function deleteTypeValidate(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $types = $arguments['types'] ?? [];

    // Require Schema.org types.
    if (empty($types)) {
      throw new \Exception(dt('Schema.org types are required'));
    }

    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);
      $this->schemaMappingManager->deleteTypeValidate($entity_type, $schema_type);
    }
  }

  /**
   * Delete Schema.org type.
   *
   * @param array $types
   *   A list of Schema.org mapping types.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command schemadotorg:delete-type
   *
   * @usage drush schemadotorg:delete-type --delete-fields user:Person
   * @usage drush schemadotorg:delete-type --delete-fields media:AudioObject media:DataDownload media:ImageObject media:VideoObject
   * @usage drush schemadotorg:delete-type --delete-entity paragraph:ContactPoint paragraph:PostalAddress
   * @usage drush schemadotorg:delete-type --delete-entity node:Person node:Organization node:Place node:Event node:CreativeWork
   *
   * @option delete-entity Delete the entity associated with the Schema.org type.
   * @option delimiter Delete the fields associated with the Schema.org type.
   *
   * @aliases sode
   */
  public function deleteType(array $types, array $options = ['delete-entity' => FALSE, 'delete-fields' => FALSE]) {
    $t_args = ['@types' => implode(', ', $types)];
    if (!$this->io()->confirm(dt('Are you sure you want to delete these Schema.org types (@types) and their associated entities and fields?', $t_args))) {
      throw new UserAbortException();
    }

    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);
      $this->schemaMappingManager->deleteType($entity_type, $schema_type, $options);
    }
    $this->io()->writeln(dt('Schema.org types (@types) deleted.', $t_args));
  }

}
