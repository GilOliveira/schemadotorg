<?php

namespace Drupal\schemadotorg;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Schema.org schema type builder service.
 */
class SchemaDotOrgSchemaTypeBuilder implements SchemaDotOrgSchemaTypeBuilderInterface {

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgSchemaTypeBuilder object.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemUrl($id) {
    return Url::fromRoute('schemadotorg_reports', ['id' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildItemsLinks($text) {
    $ids = $this->schemaTypeManager->parseIds($text);

    $links = [];
    foreach ($ids as $id) {
      $prefix = ($links) ? ', ' : '';
      if ($this->schemaTypeManager->isItem($id)) {
        $links[] = [
          '#type' => 'link',
          '#title' => $id,
          '#url' => $this->getItemUrl($id),
          '#prefix' => $prefix,
        ];
      }
      else {
        $links[] = ['#plain_text' => $id, '#prefix' => $prefix];
      }
    }
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTypeTreeRecursive(array $tree) {
    if (empty($tree)) {
      return [];
    }

    $items = [];
    foreach ($tree as $type => $item) {
      $items[$type] = [
        '#type' => 'link',
        '#title' => $type,
        '#url' => $this->getItemUrl($type),
      ];
      $children = $item['subtypes'] + $item['enumerations'];
      $items[$type]['children'] = $this->buildTypeTreeRecursive($children);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatComment($comment) {
    if (strpos($comment, 'href="/') === FALSE) {
      return $comment;
    }

    $dom = Html::load($comment);
    $a_nodes = $dom->getElementsByTagName('a');
    foreach ($a_nodes as $a_node) {
      $href = $a_node->getAttribute('href');
      if (preg_match('#^/([0-9A-Za-z]+)$#', $href, $match)) {
        $url = $this->getItemUrl($match[1]);
        $a_node->setAttribute('href', $url->toString());
      }
    }
    return Html::serialize($dom);
  }

}
