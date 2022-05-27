<?php

namespace Drupal\schemadotorg_jsonld_endpoint\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Schema.org JSON-LD endpoint routes.
 */
class SchemaDotOrgJsonLdEndpointController extends ControllerBase {

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * The Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->router = $container->get('router');
    $instance->builder = $container->get('schemadotorg_jsonld.builder');
    return $instance;
  }

  /**
   * Build the Schema.org JSON-LD response for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Schema.org JSON-LD response for an entity.
   */
  public function getEntity(EntityInterface $entity) {
    $entity_route_match = $this->getEntityCanonicalRouteMatch($entity);
    if ($entity_route_match) {
      $data = $this->builder->build($entity_route_match);
    }
    else {
      $data = $this->builder->buildEntity($entity);
      if ($data) {
        $data = ['@context' => 'https://schema.org'] + $data;
      }
    }

    if (!$data) {
      throw new NotFoundHttpException();
    }

    return new JsonResponse($data);
  }

  /**
   * Get an entity's canonical route match.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Routing\RouteMatch|null
   *   An entity's canonical route match.
   */
  protected function getEntityCanonicalRouteMatch(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    if (!$entity->hasLinkTemplate('canonical')) {
      return NULL;
    }

    $url = $entity->toUrl('canonical');
    $route_name = $url->getRouteName();
    $route_collection = $this->router->getRouteCollection();
    $route = $route_collection->get($route_name);
    if (empty($route)) {
      return NULL;
    }

    return new RouteMatch(
      $route_name,
      $route,
      [$entity_type_id => $entity],
      [$entity_type_id => $entity->id()]
    );
  }

  /**
   * Checks view access to an entity's Schema.org JSON-LD.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, EntityInterface $entity) {
    return $entity->access('view', $account, TRUE);
  }

}
