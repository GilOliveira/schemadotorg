<?php

namespace Drupal\schemadotorg_subtype\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alters the Schema.org mapping list builder and adds a 'Subtype' column.
 *
 * @see \Drupal\schemadotorg\SchemaDotOrgMappingListBuilder
 */
class SchemaDotOrgSubtypeEventSubscriber extends ServiceProviderBase implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SchemaDotOrgSubtypeEventSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Alters Schema.org mapping list builder and adds a 'JSON:API' column.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onView(ViewEvent $event) {
    if ($this->routeMatch->getRouteName() !== 'entity.schemadotorg_mapping.collection') {
      return;
    }

    $result = $event->getControllerResult();

    // Header.
    // Add 'Schema.org subtype' to header after 'Schema.org type'.
    // @see \Drupal\schemadotorg\SchemaDotOrgMappingTypeListBuilder::buildHeader
    $details_toggle = (boolean) $event->getRequest()->query->get('details') ?? 0;
    $header_width = $details_toggle ? '10%' : '27%';
    $header =& $result['table']['#header'];
    $header['bundle_label']['width'] = $header_width;
    $header['schema_type']['width'] = $header_width;
    $header_cell = [
      'data' => $this->t('Schema.org subtyping'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => '10%',
    ];
    $this->insertAfter($header, 'schema_type', 'schema_subtype', $header_cell);

    // Rows.
    // Add 'Schema.org subtype' to row after 'Schema.org type'.
    foreach ($result['table']['#rows'] as $id => &$row) {
      [$entity_type_id, $bundle] = explode('.', $id);
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
      $mapping = $this->entityTypeManager
        ->getStorage('schemadotorg_mapping')
        ->load("$entity_type_id.$bundle");
      $row_cell = $mapping->getSchemaPropertyFieldName('subtype')
        ? $this->t('Yes')
        : $this->t('No');
      $this->insertAfter($row, 'schema_type', 'schema_subtype', $row_cell);
    }

    $event->setControllerResult($result);
  }

  /**
   * Inserts a new key/value after the key in the array.
   *
   * @param array &$array
   *   An array to insert in to.
   * @param string $target_key
   *   The key to insert after.
   * @param string $new_key
   *   The key to insert.
   * @param mixed $new_value
   *   The value to insert.
   */
  protected function insertAfter(array &$array, $target_key, $new_key, $new_value) {
    $new = [];
    foreach ($array as $key => $value) {
      $new[$key] = $value;
      if ($key === $target_key) {
        $new[$new_key] = $new_value;
      }
    }
    $array = $new;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before main_content_view_subscriber.
    $events[KernelEvents::VIEW][] = ['onView', 100];
    return $events;
  }

}
