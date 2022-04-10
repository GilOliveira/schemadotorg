<?php

namespace Drupal\schemadotorg_ui\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org UI Drush commands.
 */
class SchemaDotOrgUiCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * The Schema.org UI API.
   *
   * @var \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface
   */
  protected $schemaApi;

  /**
   * SchemaDotOrgUiCommands constructor.
   *
   * @param \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface $schema_api
   *   The Schema.org UI API.
   */
  public function __construct(SchemaDotOrgUiApiInterface $schema_api) {
    parent::__construct();
    $this->schemaApi = $schema_api;
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

    /** @var \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface $schema_api */
    $schema_api = \Drupal::service('schemadotorg_ui.api');
    foreach ($types as $type) {
      // Validate mapping type.
      if (strpos($type, ':') === FALSE) {
        $t_args = ['@type' => $type];
        $message = $this->t("The Schema.org mapping type '@type' is not valid. A Schema.org type must be defined with an entity type and Schema.org type delimited using a colon (:).", $t_args);
        throw new \Exception($message);
      }

      [$entity_type_id, $schema_type] = explode(':', $type);
      $schema_api->createTypeValidate($entity_type_id, $schema_type);
    }
  }

  /**
   * Create Schema.org types.
   *
   * @param array $types
   *   A list of Schema.org mapping types.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command schemadotorg:create-type
   *
   * @usage drush schemadotorg:create-type paragraph:ContactPoint paragraph:PostalAddress
   * @usage drush schemadotorg:create-type media:AudioObject media:DataDownload media:ImageObject media:VideoObject
   * @usage drush schemadotorg:create-type user:Person
   * @usage drush schemadotorg:create-type node:Person node:Organization node:Place node:Event node:CreativeWork
   * @usage drush schemadotorg:create-type --default-properties=longitude,latitude node:Place
   * @usage drush schemadotorg:create-type --subtypes=Organization node:Organization
   *
   * @option default-properties A comma delimited list of additional default Schema.org properties.
   * @option unlimited-properties A comma delimited list of additional unlimited Schema.org properties.
   * @option subtypes A comma delimited list of Schema.org types that should support subtyping.
   *
   * @aliases socr
   */
  public function createType(array $types, array $options = ['default-properties' => NULL, 'unlimited-properties' => NULL, 'subtypes' => NULL]) {
    $t_args = ['@types' => implode(', ', $types)];
    if (!$this->io()->confirm($this->t('Are you sure you want to create these types (@types)?', $t_args))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface $schema_api */
    $schema_api = \Drupal::service('schemadotorg_ui.api');
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);
      $schema_api->createType($entity_type, $schema_type, $options);
    }
    $this->io()->writeln($this->t('Schema.org types (@types) created.', $t_args));
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

    /** @var \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface $schema_api */
    $schema_api = \Drupal::service('schemadotorg_ui.api');
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);
      $schema_api->deleteTypeValidate($entity_type, $schema_type);
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
    if (!$this->io()->confirm($this->t('Are you sure you want to delete these Schema.org types (@types) and their associated entities and fields?', $t_args))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg_ui\SchemaDotOrgUiApiInterface $schema_api */
    $schema_api = \Drupal::service('schemadotorg_ui.api');
    foreach ($types as $type) {
      [$entity_type, $schema_type] = explode(':', $type);
      $schema_api->deleteType($entity_type, $schema_type, $options);
    }
    $this->io()->writeln($this->t('Schema.org types (@types) deleted.', $t_args));
  }

}
