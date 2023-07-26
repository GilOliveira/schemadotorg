<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_diagram;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface;

/**
 * Base class for Schema.org diagram service.
 *
 * @see https://mermaid.js.org/intro/
 * @see https://mermaid.js.org/syntax/flowchart.html
 * @see https://jojozhuang.github.io/tutorial/mermaid-cheat-sheet/
 */
class SchemaDotOrgDiagram implements SchemaDotOrgDiagramInterface {
  use StringTranslationTrait;

  /**
   * Circle.
   */
  const CIRCLE = 'circle';

  /**
   * Rounded rectangle.
   */
  const ROUNDED_RECTANGLE = 'rounded_rectangle';

  /**
   * Rectangle.
   */
  const RECTANGLE = 'rectangle';

  /**
   * Max depth for hierarchy.
   *
   * @var int
   */
  protected $maxDepth = 3;

  /**
   * The parent Schema.org property.
   *
   * @var string|null
   */
  protected $parentProperty;

  /**
   * The child Schema.org property.
   *
   * @var string|null
   */
  protected $childProperty;

  /**
   * Constructs a SchemaDotOrgStarterkitHeirarchy object.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schemaTypeBuilder
   *   The Schema.org schema type builder.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeBuilderInterface $schemaTypeBuilder
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(NodeInterface $node, ?string $parent_property, ?string $child_property, ?string $title): ?array {
    $this->parentProperty = $parent_property;
    $this->childProperty = $child_property;

    // Build parent nodes output.
    $parent_output = [];
    $this->buildParentNodesOutput($parent_output, $node);

    // Build child nodes output.
    $child_output = [];
    $this->buildChildNodesOutputRecursive($child_output, $node, 2);

    // Exit, if there are no parent or child outputs.
    if (empty($parent_output) && empty($child_output)) {
      return NULL;
    }

    // Start flowchart.
    $output = ['flowchart TB'];

    // Build current node container.
    $node_id = '1-' . $node->id();
    $this->appendNodeToOutput($output, $node_id, $node, static::CIRCLE);

    // Merge parent and child output.
    $output = array_merge($output, $parent_output, $child_output);

    $build = [];

    // Title.
    if ($title) {
      $build['title'] = [
        '#markup' => $title,
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
      ];
    }

    // Properties.
    $build['properties'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    if ($this->parentProperty && $parent_output) {
      $build['properties']['parent'] = $this->schemaTypeBuilder->buildItemsLinks('https://schema.org/' . $this->parentProperty);
    }
    if (($this->parentProperty && $parent_output) && ($this->childProperty && $child_output)) {
      $build['properties']['divider'] = ['#markup' => ' ↔ '];
    }
    if ($this->childProperty && $child_output) {
      $build['properties']['child'] = $this->schemaTypeBuilder->buildItemsLinks('https://schema.org/' . $this->childProperty);
    }

    // Mermaid.js diagram.
    $build['mermaid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mermaid', 'schemadotorg-mermaid']],
      '#markup' => implode(PHP_EOL, $output),
    ];

    // Attach the mermaid.js and dialog libraries.
    $build['#attached']['library'][] = 'schemadotorg/schemadotorg.mermaid';
    $build['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';

    // @todo Determine how best to cache the diagram.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  protected function buildParentNodesOutput(array &$output, NodeInterface $node): void {
    $parent_field_name = $this->getEntityReferenceFieldName($node, $this->parentProperty);
    if (!$parent_field_name) {
      return;
    }

    $node_id = '1-' . $node->id();

    foreach ($node->$parent_field_name as $item) {
      /** @var \Drupal\node\NodeInterface $parent_node */
      $parent_node = $item->entity;

      $parent_id = '0-' . $parent_node->id();

      // Build parent container and link.
      $this->appendNodeToOutput($output, $parent_id, $parent_node, static::ROUNDED_RECTANGLE);

      // Build connector from parent to child.
      $output[] = $parent_id . ' -.- ' . $node_id;
    }
  }

  /**
   * Build child nodes recursively.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param int $depth
   *   The current depth of the recursion.
   */
  protected function buildChildNodesOutputRecursive(array &$output, NodeInterface $node, int $depth): void {
    $child_field_name = $this->getEntityReferenceFieldName($node, $this->childProperty);
    if (!$child_field_name) {
      return;
    }

    $parent_id = ($depth - 1) . '-' . $node->id();
    foreach ($node->$child_field_name as $item) {
      /** @var \Drupal\node\NodeInterface $child_node */
      $child_node = $item->entity;
      if (!$child_node) {
        continue;
      }

      $child_id = $depth . '-' . $child_node->id();

      // Build connector from parent to child with entity reference override
      // as the connector label.
      $override = $item->override ?? NULL;
      $override_format = $item->override_format ?? NULL;
      if ($override) {
        $connector_label = $override_format
          ? (string) check_markup($override, $override_format)
          : $override;
        $connector_label = Unicode::truncate($connector_label, 30, TRUE, TRUE);
        $output[] = $parent_id . ' --- |"`' . $connector_label . '`"|' .$child_id;
      }
      else {
        $output[] = $parent_id . ' --- ' . $child_id;
      }

      // Build child container and link.
      $this->appendNodeToOutput($output, $child_id, $child_node);

      if ($depth < $this->maxDepth) {
        $this->buildChildNodesOutputRecursive($output, $child_node, $depth + 1);
      }
    }
  }

  /**
   *
   */
  protected function appendNodeToOutput(array &$output, string $id, NodeInterface $node, ?string $shape = NULL): void {
    // URI.
    $node_uri = $node->toUrl()->setAbsolute()->toString();

    // Title with Schema.org type.
    $node_title = '**' . $node->label() . '**';
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    $mapping = $mapping_storage->loadByEntity($node);
    if ($mapping) {
      $field_name = $mapping->getSchemaPropertyFieldName('subtype');
      if ($field_name && $node->hasField($field_name)) {
        $schema_type = $node->get($field_name)->value ?? $mapping->getSchemaType();
        $node_title .= PHP_EOL . '(' . $schema_type . ')';
      }
    }

    // Shape with style.
    switch ($shape) {
      case static::CIRCLE;
        $output[] = $id . '(("`' . $node_title . '`"))';
        $output[] = "style $id fill:#ffaacc,stroke:#333,stroke-width:4px;";
        break;

      case static::ROUNDED_RECTANGLE;
        $output[] = $id . '("`' . $node_title . '`")';
        $output[] = "style $id stroke-dasharray: 5 5";
        break;

      case static::RECTANGLE;
      default;
        $output[] = $id . '["`' . $node_title . '`"]';
        break;
    }

    // Link.
    $output[] = 'click ' . $id . ' "' . $node_uri . '"';
  }

  /**
   * Get a node's entity reference field for a Schema.org property.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param string|null $schema_property
   *   A Schema.org property.
   *
   * @return string|null
   *   A node's entity reference field for a Schema.org property.
   */
  protected function getEntityReferenceFieldName(NodeInterface $node, ?string $schema_property): ?string {
    if (!$schema_property) {
      return NULL;
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    $mapping = $mapping_storage->loadByEntity($node);
    if (!$mapping) {
      return NULL;
    }

    $field_name = $mapping->getSchemaPropertyFieldName($schema_property);
    if (!$field_name
      || !$node->hasField($field_name)
      || !($node->$field_name instanceof EntityReferenceFieldItemListInterface)) {
      return NULL;
    }

    return $field_name;
  }

}
