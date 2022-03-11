<?php

namespace Drupal\schemadotorg_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface;
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
   * The Schema.org entity type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface
   */
  protected $schemaDotOrgEntityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaDotOrgEntityTypeManager = $container->get('schemadotorg.entity_type_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $entity_types = $this->schemaDotOrgEntityTypeManager->getEntityTypes();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $bundle_of = $entity_type->getBundleOf();
      if ($bundle_of && in_array($bundle_of, $entity_types)) {
        $this->derivatives["schemadotorg.{$entity_type_id}.type_add"] = [
          'route_name' => "schemadotorg.{$entity_type_id}.type_add",
          'parent' => "entity.{$entity_type_id}.collection",
          'title' => $this->t('Add Schema.org type'),
          'weight' => -1,
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
