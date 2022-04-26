<?php

namespace Drupal\schemadotorg_report\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a breadcrumb builder for Schema.org report.
 */
class SchemaDotOrgReportBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return (strpos($route_match->getRouteName(), 'schemadotorg_report.') === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
    if ($route_match->getRouteName() === 'schemadotorg_report.settings') {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Structure'), 'system.admin_structure'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Schema.org'), 'entity.schemadotorg_mapping.collection'));
    }
    else {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Reports'), 'system.admin_reports'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Schema.org'), 'schemadotorg_report'));
    }

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
