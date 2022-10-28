<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_layout_paragraphs;

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
 * Schema.org layout paragraphs installer.
 */
class SchemaDotOrgLayoutParagraphsInstaller implements SchemaDotOrgLayoutParagraphsInstallerInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * The Schema.org mapping manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $schemaMappingManager;

  /**
   * Constructs a SchemaDotOrgLayoutParagraphsInstaller object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $schema_mapping_manager
   *   The Schema.org mapping manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgMappingManagerInterface $schema_mapping_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->schemaNames = $schema_names;
    $this->schemaMappingManager = $schema_mapping_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function install(): void {
    // Setting weight to 1 so that the Schema.org Layout Paragraphs hooks
    // are triggered after the Schema.org Paragraphs module.
    module_set_weight('schemadotorg_layout_paragraphs', 1);

    $this->createDefaultParagraphTypes();
    $this->createMediaParagraphTypes();
  }

  /**
   * Create default paragraph types.
   */
  protected function createDefaultParagraphTypes(): void {
    $schema_types = [
      'quotation' => 'Quotation',
      'item_list' => 'ItemList',
      'statement' => 'Statement',
      'media_gallery' => 'MediaGallery',
      'image_gallery' => 'ImageGallery',
      'video_gallery' => 'VideoGallery',
    ];
    foreach ($schema_types as $paragraph_type_id => $schema_type) {
      $paragraph_type = $this->entityTypeManager
        ->getStorage('paragraphs_type')
        ->load($paragraph_type_id);
      if ($paragraph_type) {
        continue;
      }

      // Create the paragraph type and Schema.org mapping.
      if (str_ends_with($schema_type, 'Gallery')) {
        // For galleries we only want to support media selection.
        $defaults = $this->schemaMappingManager->getMappingDefaults('paragraph', NULL, $schema_type);
        $defaults['properties'] = ['hasPart' => $defaults['properties']['hasPart']];
        $this->schemaMappingManager->saveMapping('paragraph', $schema_type, $defaults);
      }
      else {
        $this->schemaMappingManager->createType('paragraph', $schema_type);
      }

      // Hide all component labels for schema_* fields.
      $display = $this->entityDisplayRepository->getViewDisplay('paragraph', $paragraph_type_id);
      $components = $display->getComponents();
      foreach ($components as $field_name => $component) {
        if (str_starts_with($field_name, 'schema_')) {
          $component['label'] = 'hidden';
          $display->setComponent($field_name, $component);
        }
      }
      $display->save();
    }
  }

  /**
   * Create media paragraph types.
   */
  protected function createMediaParagraphTypes(): void {
    /** @var \Drupal\media\MediaTypeInterface[] $media_types */
    $media_types = [
      'audio' => [
        'label' => $this->t('Audio'),
        'schema_type' => 'AudioObject',
      ],
      'image' => [
        'label' => $this->t('Image'),
        'schema_type' => 'ImageObject',
      ],
      'remote_video' => [
        'label' => $this->t('Video'),
        'schema_type' => 'VideoObject',
      ],
    ];
    foreach ($media_types as $media_type_id => $media_type_info) {
      $media_type = $this->entityTypeManager
        ->getStorage('media_type')
        ->load($media_type_id);
      if (!$media_type) {
        continue;
      }

      $paragraph_type = $this->entityTypeManager
        ->getStorage('paragraphs_type')
        ->load($media_type_id);
      if ($paragraph_type) {
        continue;
      }

      $label = $media_type_info['label'];

      // Create a paragraph type for media.
      // (i.e. paragraph_type:image => media_type:image)
      $schema_type = $media_type_info['schema_type'];
      $defaults = $this->schemaMappingManager->getMappingDefaults('paragraph', NULL, $schema_type);
      $defaults['entity']['label'] = $label;
      $defaults['entity']['id'] = $media_type_id;
      // Use the mainEntityOfPage to store a reference to a media entity.
      $main_entity_of_page = $defaults['properties']['mainEntityOfPage'];
      $main_entity_of_page['name'] = '_add_';
      $main_entity_of_page['type'] = 'field_ui:entity_reference:media';
      $defaults['properties'] = ['mainEntityOfPage' => $main_entity_of_page];
      $this->schemaMappingManager->saveMapping('paragraph', $schema_type, $defaults);

      $field_name = $this->schemaNames->getFieldPrefix() . $main_entity_of_page['machine_name'];

      /** @var \Drupal\field\FieldConfigInterface $field */
      $field = $this->entityTypeManager
        ->getStorage('field_config')
        ->load("paragraph.$media_type_id.$field_name");
      // Change the field label from 'Main entity of page' to the media type.
      $field->set('label', $label);
      // Update the field settings to target only the corresponding media type.
      $settings = $field->getSettings();
      $settings['handler_settings']['target_bundles'] = [$media_type_id => $media_type_id];
      $field->setSettings($settings);
      $field->save();

      // Hide the field label.
      $display = $this->entityDisplayRepository->getViewDisplay('paragraph', $media_type_id);
      $component = $display->getComponent($field_name);
      $component['label'] = 'hidden';
      $display->setComponent($field_name, $component);
      $display->save();
    }
  }

}
