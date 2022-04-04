<?php

namespace Drupal\schemadotorg_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides menu link definitions for all entity bundles.
 */
class SchemaDotOrgUiMenuLink extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $entity_types = $mapping_type_storage->getEntityTypes();

    $this->derivatives = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $bundle_of = $entity_type->getBundleOf();
      if ($bundle_of
        && in_array($bundle_of, $entity_types)
        // Block media from being created because it requires a source to be defined.
        // @see \Drupal\media\MediaTypeForm::form
        && $bundle_of !== 'media') {
        $this->derivatives["schemadotorg.{$entity_type_id}.type_add"] = [
          'route_name' => "schemadotorg.{$entity_type_id}.type_add",
          'parent' => "entity.{$entity_type_id}.collection",
          'title' => $this->t('Add Schema.org type'),
          'weight' => -10,
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
