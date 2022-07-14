<?php

namespace Drupal\schemadotorg_jsonld\Breadcrumb;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\schemadotorg\Breadcrumb\SchemaDotOrgBreadcrumbBuilder;

/**
 * Provides a breadcrumb builder for Schema.org JSON-LD.
 */
class SchemaDotOrgJsonLdBreadcrumbBuilder extends SchemaDotOrgBreadcrumbBuilder {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return ($route_match->getRouteName() === 'schemadotorg_jsonld.settings');
  }

}
