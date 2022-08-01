<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org help manager service.
 */
class SchemaDotOrgHelpManager implements SchemaDotOrgHelpManagerInterface {

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * Constructs a SchemaDotOrgMappingManager object.
   *
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   */
  public function __construct(ExtensionPathResolver $extension_path_resolver) {
    $this->extensionPathResolver = $extension_path_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function build($route_name, RouteMatchInterface $route_match) {
    if (strpos($route_name, 'help.page.schemadotorg') !== 0) {
      return;
    }

    $module_name = str_replace('help.page.', '', $route_name);
    $module_readme = $this->extensionPathResolver->getPath('module', $module_name) . '/README.md';
    if (!file_exists($module_readme)) {
      return;
    }

    $contents = file_get_contents($module_readme);

    // Remove the table of contents.
    $contents = preg_replace('/^.*?(Introduction\s+------------)/s', '$1', $contents);

    if (class_exists('\Michelf\Markdown')) {
      return [
        '#markup' => \Michelf\Markdown::defaultTransform($contents),
      ];
    }
    else {
      return [
        '#plain_text' => $contents,
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ];
    }
  }

}
