<?php

namespace Drupal\schemadotorg_jsonld_preview;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgJsonLdManagerInterface $schema_jsonld_manager,
    SchemaDotOrgJsonLdBuilderInterface $schema_jsonld_builder
  ) {
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

    // JSON-LD endpoint.
    // @see schemadotorg_jsonld_endpoint.module
    $entity = $this->schemaJsonLdManager->getRouteMatchEntity();
    if ($entity && $this->moduleHandler->moduleExists('schemadotorg_jsonld_endpoint')) {
      $entity_type_id = $entity->getEntityTypeId();
      $jsonld_url = Url::fromRoute(
        'schemadotorg_jsonld_endpoint.' . $entity_type_id,
        ['entity' => $entity->uuid()],
        ['absolute' => TRUE],
      );
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
    return $build;
  }

}
