<?php

namespace Drupal\schemadotorg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org help routes.
 */
class SchemaDotOrgHelpController extends ControllerBase {

  /**
   * The Schema.org help manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgHelpManagerInterface
   */
  protected $helpMananger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->helpMananger = $container->get('schemadotorg.help_manager');
    return $instance;
  }

  /**
   * Returns Schema.org help videos page.
   */
  public function videos() {
    return $this->helpMananger->buildVideosPage();
  }

}
