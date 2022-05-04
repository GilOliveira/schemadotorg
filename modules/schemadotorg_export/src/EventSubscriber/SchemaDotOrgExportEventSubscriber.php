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
 * @see \Drupal\schemadotorg_export\Controller\SchemaDotOrgExportController
 */
class SchemaDotOrgExportEventSubscriber extends ServiceProviderBase implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs an SchemaDotOrgJsonApiExtrasEventSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The RouteMatch service.
   */
  public function __construct(RouteMatchInterface $routeMatch) {
    $this->routeMatch = $routeMatch;
  }

  /**
   * Alters Schema.org mapping list builder and adds a 'Download CSV' link.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onView(ViewEvent $event) {
    if ($this->routeMatch->getRouteName() !== 'entity.schemadotorg_mapping.collection') {
      return;
    }

    $result = $event->getControllerResult();
    $result['export'] = [
      '#type' => 'link',
      '#title' => $this->t('<u>⇩</u> Download CSV'),
      '#url' => Url::fromRoute('entity.schemadotorg_mapping.export'),
      '#attributes' => ['class' => ['button', 'button--small']],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $event->setControllerResult($result);
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
