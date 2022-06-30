<?php

namespace Drupal\schemadotorg_jsonld_preview;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;

/**
 * Schema.org JSON-LD preview builder.
 */
class SchemaDotOrgJsonLdPreviewBuilder implements SchemaDotOrgJsonLdPreviewBuilderInterface {
  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $routeProvider;

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
   * The Schema.org JSON-LD manager.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface
   */
  protected $schemaJsonLdManager;

  /**
   * The Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $schemaJsonLdBuilder;

  /**
   * Constructs a SchemaDotOrgJsonLdPreviewBuilder object.
   *
   * @param \Drupal\Core\Routing\AccessAwareRouterInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager
   *   The Schema.org JSON-LD manager service.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface $schema_jsonld_builder
   *   The Schema.org JSON-LD builder service.
   */
  public function __construct(
    AccessAwareRouterInterface $route_provider,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager,
    SchemaDotOrgJsonLdBuilderInterface $schema_jsonld_builder
  ) {
    $this->routeProvider = $route_provider;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaJsonLdManager = $schema_jsonld_manager;
    $this->schemaJsonLdBuilder = $schema_jsonld_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Build the entity's Schema.org data.
    /** @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface $builder */
    $data = $this->schemaJsonLdBuilder->build();
    if (!$data) {
      return [];
    }

    // Display the JSON-LD using a details element.
    $build = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org JSON-LD'),
      '#weight' => 1000,
      '#attributes' => [
        'data-schemadotorg-details-key' => 'schemadotorg-jsonld-preview',
        'class' => ['schemadotorg-jsonld-preview', 'js-schemadotorg-jsonld-preview'],
      ],
      '#attached' => ['library' => ['schemadotorg_jsonld_preview/schemadotorg_jsonld_preview']],
    ];

    // Make it easy for someone to copy the JSON.
    $t_args = [':href' => 'https://validator.schema.org/'];
    $description = $this->t('Please copy-n-paste the below JSON-LD into the <a href=":href">Schema Markup Validator</a>.', $t_args);
    $build['copy'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['schemadotorg-jsonld-preview-copy']],
      'description' => [
        '#type' => 'container',
        '#markup' => $description,
      ],
      'button' => [
        '#type' => 'button',
        '#button_type' => 'small',
        '#attributes' => ['class' => ['schemadotorg-jsonld-preview-copy-button', 'button--extrasmall']],
        '#value' => $this->t('Copy JSON-LD'),
      ],
      'message' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => ['schemadotorg-jsonld-preview-copy-message']],
        '#plain_text' => $this->t('JSON-LD copied to clipboard…'),
      ],
    ];

    // JSON.
    // Make the JSON pretty and enhance it.
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // Escape HTML special characters.
    $json_markup = htmlspecialchars($json);
    // Add <span> tag to properties.
    $json_markup = preg_replace('/&quot;([^&]+)&quot;: /', '<span>&quot;$1&quot;</span>: ', $json_markup);
    // Add links to URLs.
    $json_markup = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.-]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $json_markup);
    $build['json'] = [
      'input' => [
        '#type' => 'hidden',
        '#value' => $json,
      ],
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#attributes' => ['class' => ['schemadotorg-jsonld-preview-code']],
        '#value' => $json_markup,
      ],
    ];

    // JSON-LD endpoint.
    // @see schemadotorg_jsonld_endpoint.module
    $entity = $this->schemaJsonLdManager->getRouteMatchEntity();
    if ($entity && $this->moduleHandler->moduleExists('schemadotorg_jsonld_endpoint')) {
      $entity_type_id = $entity->getEntityTypeId();
      $route_name = 'schemadotorg_jsonld_endpoint.' . $entity_type_id;
      $route_parameters = ['entity' => $entity->uuid()];
      $route_options = ['absolute' => TRUE];

      // Make sure the JSON-LD route exists.
      // @see \Drupal\schemadotorg_jsonld_endpoint\Routing\SchemaDotOrgJsonLdEndpointRoutes::routes
      if ($this->routeProvider->getRouteCollection()->get($route_name)) {
        $jsonld_url = Url::fromRoute($route_name, $route_parameters, $route_options);

        // Allow other modules to link to additional endpoints.
        // @see schemadotorg_taxonomy_entity_view_alter()
        $build['endpoints'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['schemadotorg-jsonld-preview-endpoints']],
        ];
        $build['endpoints'][$entity_type_id] = [
          '#type' => 'item',
          '#title' => $this->t('JSON-LD endpoint'),
          '#wrapper_attributes' => ['class' => ['container-inline']],
          'link' => [
            '#type' => 'link',
            '#url' => $jsonld_url,
            '#title' => $jsonld_url->toString(),
          ],
        ];
      }
    }
    return $build;
  }

}
