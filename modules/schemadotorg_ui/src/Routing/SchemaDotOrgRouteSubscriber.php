<?php

namespace Drupal\schemadotorg_ui\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Schema.org UI routes.
 *
 * @see \Drupal\field_ui\Routing\RouteSubscriber
 */
class SchemaDotOrgRouteSubscriber extends RouteSubscriberBase {

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
   * Constructs a SchemaDotOrgRouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface $schemadotorg_entity_type_manager
   *   The Schema.org entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgEntityTypeManagerInterface $schemadotorg_entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaDotOrgEntityTypeManager = $schemadotorg_entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $supported_entity_types = $this->schemaDotOrgEntityTypeManager->getEntityTypes();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Make sure the entity is supported.
      if (!in_array($entity_type_id, $supported_entity_types)) {
        continue;
      }

      // Make sure the entity has a field UI.
      $route_name = $entity_type->get('field_ui_base_route');
      if (!$route_name) {
        continue;
      }

      // Try to get the route from the current collection.
      $entity_route = $collection->get($route_name);
      if (!$entity_route) {
        continue;
      }

      $path = $entity_route->getPath();

      $options = $entity_route->getOptions();
      $bundle_entity_type = $entity_type->getBundleEntityType();
      if ($bundle_entity_type) {
        $options['parameters'][$bundle_entity_type] = [
          'type' => 'entity:' . $bundle_entity_type,
        ];
      }
      // Special parameter used to easily recognize all Field UI routes.
      $options['_field_ui'] = TRUE;

      $defaults = [
        'entity_type_id' => $entity_type_id,

      ];
      // If the entity type has no bundles and it doesn't use {bundle} in its
      // admin path, use the entity type.
      if (strpos($path, '{bundle}') === FALSE) {
        $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
      }

      $requirements = ['_permission' => 'administer ' . $entity_type_id . ' fields'];

      // Add 'Manage Schema.org fields' route.
      $route = new Route(
        "$path/schemedotorg",
        [
          '_title' => 'Manage Schema.org fields',
          '_entity_form' => 'schemadotorg_mapping.edit',
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("entity.{$entity_type_id}.schemadotorg_mapping", $route);

      // Add 'Add Schema.org type' route. (except media)
      $entity_collection_route = $collection->get("entity.{$bundle_entity_type}.collection");
      if ($bundle_entity_type
        && $entity_collection_route
        // Block media from being created because it requires a source to be defined.
        // @see \Drupal\media\MediaTypeForm::form
        && $entity_type_id !== 'media') {
        $entity_collection_path = $entity_collection_route->getPath();
        $route = new Route(
          "$entity_collection_path/schemadotorg",
          [
            '_title' => 'Add Schema.org type',
            '_entity_form' => 'schemadotorg_mapping.add',
          ] + $defaults,
          $requirements,
        );
        $collection->add("schemadotorg.{$bundle_entity_type}.type_add", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

}