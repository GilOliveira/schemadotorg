<?php

namespace Drupal\schemadotorg_jsonld_endpoint\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Schema.org JSON-LD endpoint routes.
 */
class SchemaDotOrgJsonLdEndpointController extends ControllerBase {

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
    $data = $this->builder->buildEntity($entity);
    if (!$data) {
      throw new NotFoundHttpException();
    }
    return new JsonResponse(['@context' => 'https://schema.org'] + $data);
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
