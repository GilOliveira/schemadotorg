<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit;

use Drupal\Component\Serialization\Yaml;
use Drupal\config_rewrite\ConfigRewriter;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;

/**
 * Schema.org starterkit manager service.
 */
class SchemaDotOrgStarterkitManager implements SchemaDotOrgStarterkitManagerInterface {

  /**
   * Constructs a SchemaDotOrgStarterkitManager object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   The module extension list.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\config_rewrite\ConfigRewriter|null $configRewriter
   *   The configuration rewrite.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $schemaMappingManager
   *   The Schema.org mapping manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface $schemaConfigManager
   *   The Schema.org config manager.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected ModuleExtensionList $extensionListModule,
    protected ConfigFactoryInterface $configFactory,
    protected ?ConfigRewriter $configRewriter,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgMappingManagerInterface $schemaMappingManager,
    protected SchemaDotOrgConfigManagerInterface $schemaConfigManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function preinstall(string $module): void {
    if (!$this->isStarterkit($module)) {
      return;
    }

    $this->rewriteSchemaConfig($module);
    $this->setupSchemaTypes($module);
  }

  /**
   * {@inheritdoc}
   */
  public function installed(array $modules): void {
    if (!$this->configRewriter) {
      return;
    }

    $has_schema_config_rewrite = FALSE;
    foreach ($modules as $module) {
      if (!$this->isStarterkit($module)) {
        continue;
      }
      $module_path = $this->extensionListModule->getPath($module);
      $rewrite_dir = "$module_path/config/rewrite";
      $has_schema_config_rewrite = file_exists($rewrite_dir)
        && $this->fileSystem->scanDirectory($rewrite_dir, '/^schemadotorg.*\.yml$/i', ['recurse' => FALSE]);
      if ($has_schema_config_rewrite) {
        break;
      }
    }

    // Repair configuration if the starter kit has written any
    // schemadotorg* configuration.
    // @see https://www.drupal.org/project/config_rewrite/issues/3152228
    if ($has_schema_config_rewrite) {
      $this->schemaConfigManager->repair();
    }
  }

  /**
   * Determine if a module is Schema.org Blueprints Starterkit.
   *
   * @param string $module
   *   A module.
   *
   * @return bool
   *   TRUE if a module is Schema.org Blueprints Starterkit.
   */
  protected function isStarterkit(string $module): bool {
    $module_path = $this->extensionListModule->getPath($module);
    $module_schemadotorg_path = "$module_path/$module.schemadotorg_starterkit.yml";
    return file_exists($module_schemadotorg_path);
  }

  /**
   * Get a module's Schema.org Blueprints starter kit settings.
   *
   * @param string $module
   *   A module.
   *
   * @return false|array
   *   A module's Schema.org Blueprints starter kit settings.
   *   FALSE if the module is not a Schema.org Blueprints starter kit
   */
  protected function getSettings(string $module): FALSE|array {
    $module_path = $this->extensionListModule->getPath($module);
    $module_schemadotorg_path = "$module_path/$module.schemadotorg_starterkit.yml";
    return (file_exists($module_schemadotorg_path))
      ? Yaml::decode(file_get_contents($module_schemadotorg_path))
      : FALSE;
  }

  /**
   * Rewrite Schema.org Blueprints related configuration.
   *
   * Scan the rewrite directory for schemadotorg.* config rewrites that need
   * to be installed before any Schema.org types are created.
   *
   * @param string $module
   *   A module.
   */
  protected function rewriteSchemaConfig(string $module): void {
    if (is_null($this->configRewriter)) {
      return;
    }

    $module_path = $this->extensionListModule->getPath($module);
    $rewrite_dir = "$module_path/config/rewrite";
    if (!file_exists($rewrite_dir)) {
      return;
    }

    $files = $this->fileSystem->scanDirectory($rewrite_dir, '/^schemadotorg.*\.yml$/i', ['recurse' => FALSE]) ?: [];
    if (empty($files)) {
      return;
    }

    foreach ($files as $file) {
      $contents = file_get_contents($rewrite_dir . DIRECTORY_SEPARATOR . $file->name . '.yml');
      $rewrite = Yaml::decode($contents);
      $config = $this->configFactory->getEditable($file->name);
      $original_data = $config->getRawData();
      $rewrite = $this->configRewriter->rewriteConfig($original_data, $rewrite, $file->name, $module);
      $config->setData($rewrite)->save();
    }
  }

  /**
   * Setup a starterkit module based on the module's settings.
   *
   * @param string $module
   *   A module.
   */
  protected function setupSchemaTypes(string $module): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    $settings = $this->getSettings($module);
    $types = $settings['types'] ?? [];
    foreach ($types as $type => $defaults) {
      [$entity_type, $schema_type] = explode(':', $type);
      if (!$mapping_storage->loadBySchemaType($entity_type, $schema_type)) {
        $this->schemaMappingManager->createType($entity_type, $schema_type, $defaults);
      }
    }
  }

}
