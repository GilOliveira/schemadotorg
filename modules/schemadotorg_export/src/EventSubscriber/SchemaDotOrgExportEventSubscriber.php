<?php

namespace Drupal\schemadotorg_export\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alters Schema.org mapping list builder and adds a 'Download CSV' link.
 *
 * @see \Drupal\schemadotorg_export\Controller\SchemaDotOrgExportMappingController
 */
class SchemaDotOrgExportEventSubscriber extends ServiceProviderBase implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a SchemaDotOrgJsonApiExtrasEventSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Alters Schema.org mapping list builder and adds a 'Download CSV' link.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onView(ViewEvent $event) {
    $route = [
      'entity.schemadotorg_mapping.collection' => 'entity.schemadotorg_mapping.export',
      'schemadotorg_mapping_set.overview' => 'schemadotorg_mapping_set.export',
    ];
    $route_name = $this->routeMatch->getRouteName();
    if (isset($route[$route_name])) {
      $result = $event->getControllerResult();
      $result['export'] = [
        '#type' => 'link',
        '#title' => $this->t('<u>⇩</u> Download CSV'),
        '#url' => Url::fromRoute($route[$route_name]),
        '#attributes' => ['class' => ['button', 'button--small', 'button--extrasmall']],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      $event->setControllerResult($result);
    }
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
