<?php

namespace Drupal\schemadotorg_jsonld\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Schema.org JSON-LD routes.
 */
class SchemaDotOrgJsonLdController extends ControllerBase {

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
   * Build the JSON-LD response for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON-LD response for an entity.
   */
  public function getEntity(EntityInterface $entity) {
    $data = $this->builder->build($entity);
    if (!$data) {
      throw new NotFoundHttpException();
    }
    return new JsonResponse($data);
  }

}
