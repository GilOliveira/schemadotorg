<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org help manager interface.
 */
interface SchemaDotOrgHelpManagerInterface {

  /**
   * Builds a help page for a Schema.org module's README.md contents.
   *
   * @param string $route_name
   *   For page-specific help, use the route name as identified in the
   *   module's routing.yml file. For module overview help, the route name
   *   will be in the form of "help.page.$modulename".
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match. This can be used to generate different help
   *   output for different pages that share the same route.
   *
   * @return null|array
   *   A render array containing the Schema.org module's README.md contents.
   */
  public function buildHelpPage($route_name, RouteMatchInterface $route_match);

  /**
   * Get a module's video as a renderable array.
   *
   * @return array
   *   A module's videos as a renderable array.
   */
  public function buildVideosPage();

}
