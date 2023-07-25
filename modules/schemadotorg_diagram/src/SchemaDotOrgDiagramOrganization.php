<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_diagram;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Schema.org diagram organization service.
 *
 * @see https://mermaid.js.org/intro/
 * @see https://mermaid.js.org/syntax/flowchart.html
 * @see https://jojozhuang.github.io/tutorial/mermaid-cheat-sheet/
 */
class SchemaDotOrgDiagramOrganization implements SchemaDotOrgDiagramOrganizationInterface {
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
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(NodeInterface $node): ?array {
    // Check that the node has parent or sub organization fields.
    $parent_organization_field_name = $this->getEntityReferenceFieldName($node, 'parentOrganization');
    $sub_organization_field_name = $this->getEntityReferenceFieldName($node, 'subOrganization');
    if (!$parent_organization_field_name && !$sub_organization_field_name) {
      return NULL;
    }

    // Start flowchart.
    $output = ['flowchart TB'];

    // Build current node container.
    $node_id = '1-' . $node->id();
    $this->appendNodeToOutput($output, $node_id, $node, static::CIRCLE);

    // Build parent nodes.
    // @see https://schema.org/parentOrganization
    if ($parent_organization_field_name) {
      foreach ($node->$parent_organization_field_name as $item) {
        /** @var \Drupal\node\NodeInterface $parent_node */
        $parent_node = $item->entity;

        $parent_id = '0-' . $parent_node->id();

        // Build parent container and link.
        $this->appendNodeToOutput($output, $parent_id, $parent_node, static::ROUNDED_RECTANGLE);

        // Build connector from parent to child.
        $output[] = $parent_id . ' -.- ' . $node_id;
      }
    }

    // Build child nodes.
    // @see https://schema.org/subOrganization
    $this->buildFlowChartRecursive($output, $node, 2);

    $build = [];

    // Organization title.
    $build['title'] = [
      '#markup' => $this->t('Organization'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];

    // Mermaid.js diagram.
    $build['mermaid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mermaid', 'schemadotorg-mermaid']],
      '#markup' => implode(PHP_EOL, $output),
    ];

    // Attach the mermaid.js library.
    $build['#attached']['library'] = ['schemadotorg/schemadotorg.mermaid'];

    // @todo Determine how best to cache the diagram.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Build flow chart recursively.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param int $depth
   *   The current depth of the recursion.
   */
  protected function buildFlowChartRecursive(array &$output, NodeInterface $node, int $depth): void {
    $sub_organization_field_name = $this->getEntityReferenceFieldName($node, 'subOrganization');
    if (!$sub_organization_field_name) {
      return;
    }

    $parent_id = ($depth - 1) . '-' . $node->id();
    foreach ($node->$sub_organization_field_name as $item) {
      /** @var \Drupal\node\NodeInterface $child_node */
      $child_node = $item->entity;

      $child_id = $depth . '-' . $child_node->id();

      // Build connector from parent to child.
      $output[] = $parent_id . ' --- ' . $child_id;

      // Build child container and link.
      $this->appendNodeToOutput($output, $child_id, $child_node);

      if ($depth < $this->maxDepth) {
        $this->buildFlowChartRecursive($output, $child_node, $depth + 1);
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
   * @param string $schema_property
   *   A Schema.org property.
   *
   * @return string|null
   *   A node's entity reference field for a Schema.org property.
   */
  protected function getEntityReferenceFieldName(NodeInterface $node, string $schema_property): ?string {
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
