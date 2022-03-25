<?php

namespace Drupal\schemadotorg;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Schema.org schema type builder service.
 */
class SchemaDotOrgSchemaTypeBuilder implements SchemaDotOrgSchemaTypeBuilderInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgSchemaTypeBuilder object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, AccountInterface $current_user, SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemUrl($id) {
    return ($this->moduleHandler->moduleExists('schemadotorg_report')
      && $this->currentUser->hasPermission('access site reports'))
      ? Url::fromRoute('schemadotorg_reports', ['id' => $id])
      : Url::fromUri('https://schema.org/' . $id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildItemsLinks($text, array $options = []) {
    $options += ['attributes' => []];

    $ids = (is_string($text)) ? $this->schemaTypeManager->parseIds($text) : $text;
    $links = [];
    foreach ($ids as $id) {
      $prefix = ($links) ? ', ' : '';
      if ($this->schemaTypeManager->isItem($id)) {
        $links[] = [
          '#type' => 'link',
          '#title' => $id,
          '#url' => $this->getItemUrl($id),
          '#prefix' => $prefix,
          '#attributes' => $options['attributes'],
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
  public function buildTypeTree(array $tree, array $options = []) {
    $options += [
      'base_path' => $this->getDefaultBasePath(),
      'attributes' => [],
    ];
    $build = $this->buildTypeTreeRecursive($tree, $options);
    $build['#attributes'] = ['class' => ['schemadotorg-jstree']];
    $build['#attached']['library'][] = 'schemadotorg/schemadotorg.jstree';
    return $build;
  }

  /**
   * Build Schema.org type tree as an item list recursively.
   *
   * @param array $tree
   *   An array of Schema.org type tree.
   * @param array $options
   *   Options which include:
   *   - base_path.
   *   - attributes.
   *
   * @return array
   *   A renderable array containing Schema.org type tree as an item list.
   *
   * @see \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManager::getTypesChildrenRecursive
   */
  protected function buildTypeTreeRecursive(array $tree, array $options = []) {
    if (empty($tree)) {
      return [];
    }

    $items = [];
    foreach ($tree as $type => $item) {
      $items[$type] = [
        '#type' => 'link',
        '#title' => $type,
        '#url' => Url::fromUri($options['base_path'] . $type),
      ];
      $item += ['subtypes' => [], 'enumerations' => []];
      $children = $item['subtypes'] + $item['enumerations'];
      $items[$type]['children'] = $this->buildTypeTreeRecursive($children, $options);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatComment($comment, array $options = []) {
    $options += [
      'base_path' => $this->getDefaultBasePath(),
      'attributes' => [],
    ];

    if (strpos($comment, 'href="') === FALSE) {
      return $comment;
    }

    $dom = Html::load($comment);
    $a_nodes = $dom->getElementsByTagName('a');
    foreach ($a_nodes as $a_node) {
      $a_node->removeAttribute('class');
      foreach ($options['attributes'] as $attribute_name => $attribute_value) {
        $a_node->setAttribute($attribute_name, $attribute_value);
      }

      $href = $a_node->getAttribute('href');
      if (preg_match('#^/([0-9A-Za-z]+)$#', $href, $match)) {
        $a_node->setAttribute('href', $options['base_path'] . $match[1]);
      }
      elseif (strpos($href, '/') === 0) {
        $a_node->setAttribute('href', 'https://schema.org' . $href);
      }
    }
    return Html::serialize($dom);
  }

  /**
   * Gets the default Schema.org base path for the current user.
   *
   * @return string
   *   The default Schema.org base path for the current user.
   */
  protected function getDefaultBasePath() {
    return ($this->moduleHandler->moduleExists('schemadotorg_report')
      && $this->currentUser->hasPermission('access site reports'))
      ? Url::fromRoute('schemadotorg_reports')->setAbsolute()->toString() . '/'
      : 'https://schema.org/';
  }

}
