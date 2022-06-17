<?php

namespace Drupal\schemadotorg\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field_ui\FieldUI;
use Drupal\schemadotorg\SchemaDotOrgInstallerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Drush commands.
 */
class SchemaDotOrgCommands extends DrushCommands {
  use StringTranslationTrait;

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
   * SchemaDotOrgCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $schema_installer
   *   The Schema.org installer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SchemaDotOrgInstallerInterface $schema_installer) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaInstaller = $schema_installer;
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
    if (!$this->io()->confirm($this->t('Are you sure you want to repair Schema.org relationships?'))) {
      throw new UserAbortException();
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    /** @var \Drupal\field\FieldConfigStorage $field_storage */
    $field_storage = $this->entityTypeManager->getStorage('field_config');

    $entity_ids = $field_storage->getQuery()
      ->condition('field_type', ['entity_reference', 'entity_reference_revisions'], 'IN')
      ->sort('id')
      ->execute();

    /** @var \Drupal\Core\Field\FieldConfigInterface[] $fields */
    $fields = $field_storage->loadMultiple($entity_ids);
    foreach ($fields as $field) {
      $field_name = $field->getName();
      $field_type = $field->getType();
      $entity_type_id = $field->getTargetEntityTypeId();
      $bundle = $field->getTargetBundle();
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
      $mapping = $mapping_storage->load("$entity_type_id.$bundle");
      if (!$mapping) {
        continue;
      }

      $schema_type = $mapping->getSchemaType();
      $schema_property = $mapping->getSchemaPropertyMapping($field_name);
      if (!$schema_property) {
        continue;
      }

      // Get expected target bundles.
      $target_type = $field->getSetting('target_type');
      $expected_target_bundles = $mapping_storage->getSchemaPropertyTargetBundles($target_type, $schema_type, $schema_property);

      // Get actual target bundles.
      $handler_settings = $field->getSetting('handler_settings');
      $actual_target_bundles = $handler_settings['target_bundles'];

      // Manually sync paragraph:from_library.
      if ($target_type === 'paragraph' && isset($actual_target_bundles['from_library'])) {
        $expected_target_bundles['from_library'] = 'from_library';
      }

      // Skip if the expected and actual target bundles matches.
      if ($expected_target_bundles == $actual_target_bundles) {
        continue;
      }

      // Update target bundles to match expected.
      $handler_settings['target_bundles'] = $expected_target_bundles;
      $field->setSetting('handler_settings', $handler_settings);
      $field->save();

      // Display success message.
      $t_args = [
        '@entity_type' => $entity_type_id,
        '@field_name' => $field_name,
        '@field_type' => $field_type,
        '@schema_type' => $schema_type,
        '@schema_property' => $schema_property,
        '@bundles' => implode(', ', $expected_target_bundles),
      ];
      $message = $this->t("Updated @entity_type:@field_name (@schema_type:@schema_property) '@field_type' field to target '@bundles'.", $t_args);
      $this->io->success($message);
    }
  }

}
