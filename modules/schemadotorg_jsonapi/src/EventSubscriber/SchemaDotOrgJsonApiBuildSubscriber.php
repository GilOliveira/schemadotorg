<?php

namespace Drupal\schemadotorg_jsonapi\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;
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
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * Constructs a SchemaDotOrgJsonApiBuildSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SchemaDotOrgNamesInterface $schema_names) {
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaNames = $schema_names;
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
    if ($mapping->supportsSubtyping()) {
      $properties[$this->schemaNames->getSubtypeFieldName()] = 'subtype';
    }
    foreach ($properties as $internal_name => $property) {
      if (isset($fields[$internal_name])) {
        $event->setPublicFieldName($fields[$internal_name], $property);
      }
    }
  }

}
