<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_help\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Schema.org Blueprints help routes.
 */
class SchemaDotOrgHelpController extends ControllerBase {

  /**
   * The Schema.org help manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgHelpManagerInterface
   */
  protected $helpManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->helpManager = $container->get('schemadotorg_help.manager');
    return $instance;
  }

  /**
   * Prints a page listing general help for a module.
   *
   * @param string $name
   *   A module name to display a help page for.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function helpPage(string $name): array {
    return $this->helpManager->buildHelpPage($name);
  }

  /**
   * Returns Schema.org help videos page.
   */
  public function videos(): array {
    return $this->helpManager->buildVideosPage();
  }

}
