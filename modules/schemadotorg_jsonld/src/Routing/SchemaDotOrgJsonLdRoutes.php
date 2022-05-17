<?php

namespace Drupal\schemadotorg_jsonld\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class SchemaDotOrgJsonLdRoutes implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->configFactory = $container->get('config.factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $config = $this->configFactory->get('schemadotorg_jsonld.settings');

    $routes = new RouteCollection();

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $entity_type_ids = $mapping_type_storage->getEntityTypes();
    foreach ($entity_type_ids as $entity_type_id) {
      $entity_type_path = $config->get('entity_type_resource_paths.' . $entity_type_id) ?: $entity_type_id;

      $name = 'schemadotorg_jsonld.' . $entity_type_id;
      $path = "/jsonld/" . $entity_type_path . "/{entity}";
      $defaults = [
        '_controller' => '\Drupal\schemadotorg_jsonld\Controller\SchemaDotOrgJsonLdController:getEntity',
      ];
      $requirements = [
        '_access' => 'TRUE',
        // @todo Determine why entity access checking is not working as expected.
        // '_entity_access' => "{$entity_type_id}.view",
      ];
      $options = [
        'parameters' => [
          'entity' => ['type' => 'entity:' . $entity_type_id],
        ],
      ];
      $route = new Route($path, $defaults, $requirements, $options);
      $route->setMethods(['GET']);
      $routes->add($name, $route);
    }

    return $routes;
  }

}
