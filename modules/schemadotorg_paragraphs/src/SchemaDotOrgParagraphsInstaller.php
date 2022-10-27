<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_paragraphs;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;

/**
 * Schema.org paragraphs installer.
 */
class SchemaDotOrgParagraphsInstaller implements SchemaDotOrgParagraphsInstallerInterface {

  /**
   * The file handler.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SchemaDotOrgParagraphsInstaller object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    FileSystemInterface $file_system,
    ModuleExtensionList $extension_list_module,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->fileSystem = $file_system;
    $this->extensionListModule = $extension_list_module;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraphTypeIcons(string $module_name): void {
    $path = $this->extensionListModule->getPath($module_name) . '/images/icons';
    $files = $this->fileSystem->scanDirectory($path, '/\.svg$/');
    foreach ($files as $file) {
      $paragraph_type = ParagraphsType::load($file->name);
      if ($paragraph_type && !$paragraph_type->getIconFile()) {
        $file_entity = File::create(['uri' => $file->uri]);
        $file_entity->save();
        $paragraph_type
          ->set('icon_uuid', $file_entity->uuid())
          ->save();
      }
    }
  }

}
