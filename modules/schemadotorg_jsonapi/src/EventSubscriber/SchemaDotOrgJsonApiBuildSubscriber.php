<?php

namespace Drupal\schemadotorg_jsonapi\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * JSON API build subscriber that applies mapped Schema.org property names to the API.
 *
 * @see \Drupal\jsonapi_extras\EventSubscriber\JsonApiBuildSubscriber
 */
class SchemaDotOrgJsonApiBuildSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SchemaDotOrgJsonApiBuildSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * What events to subscribe to.
   */
  public static function getSubscribedEvents() {
    $events[ResourceTypeBuildEvents::BUILD][] = ['applyMapping'];
    return $events;
  }

  /**
   * Apply resource config through the event.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event used to change the resources and fields.
   */
  public function applyMapping(ResourceTypeBuildEvent $event) {
    [$entity_type_id, $bundle] = explode('--', $event->getResourceTypeName());

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping')
      ->load("$entity_type_id.$bundle");
    if (!$mapping) {
      return;
    }

    $fields = $event->getFields();
    $properties = $mapping->getSchemaProperties();
    foreach ($properties as $internal_name => $property) {
      if (isset($fields[$internal_name])) {
        $event->setPublicFieldName($fields[$internal_name], $property);
      }
    }
  }

}
