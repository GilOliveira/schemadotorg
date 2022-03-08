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
   * The entity type manager service.
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
   *   The entity type manager service.
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
    $entity_types = $this->schemaDotOrgEntityTypeManager->getEntityTypes();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Add 'Add Schema.org type' routes.
      $bundle_of = $entity_type->getBundleOf();
      if ($bundle_of
        && $collection->get("entity.{$entity_type_id}.collection")
        && in_array($bundle_of, $entity_types)) {
        $path = $collection->get("entity.{$entity_type_id}.collection")->getPath();
        $route = new Route(
          "$path/schemadotorg",
          [
            '_form' => '\Drupal\schemadotorg_ui\Form\SchemaDotOrgUiFieldsForm',
            '_title' => 'Add Schema.org type',
            'entity_type_id' => $entity_type_id,
          ],
          ['_permission' => 'administer ' . $entity_type_id . ' fields'],
        );
        $collection->add("schemadotorg.{$entity_type_id}.type_add", $route);
      }

      // Add 'Manage Fields: Schema.org' routes.
      $route_name = $entity_type->get('field_ui_base_route');
      if ($route_name
        && $collection->get($route_name)
        && in_array($entity_type_id, $entity_types)) {
        $entity_route = $collection->get($route_name);
        $path = $entity_route->getPath();

        $options = $entity_route->getOptions();
        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
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

        $route = new Route(
          "$path/schemadotorg",
          [
            '_form' => '\Drupal\schemadotorg_ui\Form\SchemaDotOrgUiFieldsForm',
            '_title' => 'Manage Schema.org fields',
          ] + $defaults,
          ['_permission' => 'administer ' . $entity_type_id . ' fields'],
          $options
        );
        $collection->add("entity.{$entity_type_id}.schemadotorg_fields", $route);
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
