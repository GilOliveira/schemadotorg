<?php

namespace Drupal\schemadotorg_jsonld\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes for Schema.org JSON-LD module.
 */
class SchemaDotOrgJsonLdRoutes implements ContainerInjectionInterface {

  /**
   * A key with which to flag a route as belonging to the JSON:API module.
   *
   * @var string
   */
  const JSONLD_ROUTE_FLAG_KEY = '_is_schemadotorg_jsonld';

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

      $name = 'schemadotorg_jsonld.entity.' . $entity_type_id;
      $path = "/jsonld/" . $entity_type_path . "/{entity}";
      $defaults = [
        '_controller' => '\Drupal\schemadotorg_jsonld\Controller\SchemaDotOrgJsonLdController::getEntity',
        // Flag route as belonging to the Schema.org JSON-LD module.
        // @see \Drupal\schemadotorg_jsonld\ParamConverter\EntityUuidConverter::applies
        static::JSONLD_ROUTE_FLAG_KEY => TRUE,
      ];
      $requirements = [
        '_custom_access' => '\Drupal\schemadotorg_jsonld\Controller\SchemaDotOrgJsonLdController::access',
      ];
      $options = [
        'parameters' => [
          'entity' => ['type' => 'entity:' . $entity_type_id],
        ],
      ];

      $route = (new Route($path))
        ->setDefaults($defaults)
        ->setRequirements($requirements)
        ->setOptions($options)
        ->setMethods(['GET']);

      $routes->add($name, $route);
    }

    return $routes;
  }

}
