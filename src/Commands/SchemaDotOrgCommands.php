<?php

namespace Drupal\schemadotorg\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgInstallerInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Drush commands.
 */
class SchemaDotOrgCommands extends DrushCommands {
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
   * The Schema.org installer service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface
   */
  protected $schemaInstaller;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * SchemaDotOrgCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $schema_installer
   *   The Schema.org installer service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgInstallerInterface $schema_installer,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
  ) {
    parent::__construct();
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaInstaller = $schema_installer;
    $this->schemaTypeManager = $schema_type_manager;
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

    $this->repairConfiguration();
    $this->repairRelationships();
  }

  /**
   * Repair configuration.
   */
  protected function repairConfiguration() {
    $config = $this->configFactory->getEditable('schemadotorg.settings');

    // Default properties sorted by path/breadcrumb.
    $default_properties = $config->get('schema_types.default_properties');
    $paths = [];
    foreach (array_keys($default_properties) as $type) {
      $breadcrumbs = $this->schemaTypeManager->getTypeBreadcrumbs($type);
      $path = array_key_first($breadcrumbs);
      $paths[$path] = $type;
    }
    ksort($paths);
    $sorted_default_properties = [];
    foreach ($paths as $type) {
      $properties = $default_properties[$type];
      sort($properties);
      $sorted_default_properties[$type] = $properties;
    }
    $config->set('schema_types.default_properties', $sorted_default_properties);

    // Sorting.
    $sort = [
      'ksort' => [
        'schema_types.main_properties',
        'schema_properties.range_includes',
        'schema_properties.default_fields',
        'names.custom_words',
        'names.custom_names',
        'names.prefixes',
        'names.suffixes',
        'names.abbreviations',
      ],
      'sort' => [
        'schema_properties.ignored_properties',
        'names.acronyms',
        'names.minor_words',
      ],
    ];
    foreach ($sort as $method => $keys) {
      foreach ($keys as $key) {
        $value = $config->get($key);
        if (!$value) {
          throw new \Exception('Unable to locate ' . $key);
        }
        $method($value);
        $config->set($key, $value);
      }
    }

    $config->save();
  }

  /**
   * Repair relationships.
   */
  protected function repairRelationships() {
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

      // Update paragraph's weighted target bundles to match expected.
      if (isset($handler_settings['target_bundles_drag_drop'])) {
        $weight = 0;
        foreach ($handler_settings['target_bundles'] as $target_bundle) {
          $handler_settings['target_bundles_drag_drop'][$target_bundle] = [
            'weight' => $weight,
            'enabled' => TRUE,
          ];
          $weight++;
        }
      }

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
