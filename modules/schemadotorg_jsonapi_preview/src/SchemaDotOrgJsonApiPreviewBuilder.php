<?php

namespace Drupal\schemadotorg_jsonapi_preview;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiBuilderInterface;
use Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManagerInterface;

/**
 * Schema.org JSON:API preview builder.
 */
class SchemaDotOrgJsonApiPreviewBuilder implements SchemaDotOrgJsonApiPreviewBuilderInterface {
  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity to JSON:API service.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi
   */
  protected $entityToJsonApi;

  /**
   * Constructs a SchemaDotOrgJsonApiPreviewBuilder object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entity_to_jsonapi
   *   The entity to JSON:API service.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    RouteMatchInterface $route_match,
    EntityToJsonApi $entity_to_jsonapi
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
    $this->entityToJsonApi = $entity_to_jsonapi;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->getRouteMatchEntity();
    if (!$entity) {
      return NULL;
    }

    // Retrieve JSON API representation of this node.
    $render_context = new RenderContext();
    $data = $this->renderer->executeInRenderContext($render_context, function () use ($entity) {
      try {
        return $this->entityToJsonApi->normalize($entity);
      }
      catch (\Exception $exception) {
        return NULL;
      }
    });

    if (!$data) {
      return NULL;
    }

    // Display the JSON:API using a details element.
    $build = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org JSON:API'),
      '#weight' => 1000,
      '#attributes' => [
        'data-schemadotorg-details-key' => 'schemadotorg-jsonapi-preview',
        'class' => ['schemadotorg-jsonapi-preview', 'js-schemadotorg-jsonapi-preview'],
      ],
      '#attached' => ['library' => ['schemadotorg_jsonapi_preview/schemadotorg_jsonapi_preview']],
    ];

    // JSON.
    // Make the JSON pretty and enhance it.
    // Generate markup.
    $flags = JSON_HEX_TAG
      | JSON_HEX_APOS
      | JSON_HEX_AMP
      | JSON_HEX_QUOT
      | JSON_PRETTY_PRINT;
    $json = json_encode($data, $flags);
    $build['json'] = [
      'input' => [
        '#type' => 'hidden',
        '#value' => $json,
      ],
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#attributes' => ['class' => ['prettyprint']],
        'code' => [
          '#type' => 'html_tag',
          '#tag' => 'code',
          '#attributes' => ['class' => ['language-js']],
          '#value' => $json,
        ],
      ],
    ];
    return $build;
  }

  /**
   * Returns the entity of the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or NULL if this is not an entity route.
   *
   * @see metatag_get_route_entity()
   */
  protected function getRouteMatchEntity() {
    $route_name = $this->routeMatch->getRouteName();
    if (preg_match('/entity\.(.*)\.(latest[_-]version|canonical)/', $route_name, $matches)) {
      return $this->routeMatch->getParameter($matches[1]);
    }
    else {
      return NULL;
    }
  }

}
