<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Schema.org config entities.
 */
abstract class SchemaDotOrgConfigEntityListBuilderBase extends ConfigEntityListBuilder {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Details links.
    // @see \Drupal\Core\Render\Element\SystemCompactLink
    $details_toggle = $this->getDetailsToggle();

    $t_args = ['@type' => $this->storage->getEntityType()->getSingularLabel()];

    $title = $details_toggle
      ? $this->t('Hide details')
      : $this->t('Show details');
    $attributes_title = $details_toggle
      ? $this->t('Hide @type details', $t_args)
      : $this->t('Show @type details', $t_args);
    $url = Url::fromRoute('<current>', [], ['query' => ['details' => (int) !$details_toggle]]);

    $build['details_link'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['compact-link']],
      'link' => [
        '#type' => 'link',
        '#title' => $title,
        '#url' => $url,
        '#attributes' => ['title' => $attributes_title],
      ],
    ];

    $build += parent::render();

    return $build;
  }

  /**
   * Get the current request details toggle state.
   *
   * @return bool|int
   *   The current request details toggle state.
   */
  protected function getDetailsToggle() {
    return (boolean) $this->request->query->get('details') ?? 0;
  }

  /**
   * Build a source to destination mapping.
   *
   * @param array $items
   *   An associative array with the source as the key and destination
   *   as the value.
   *
   * @return array
   *   A renderable array containing a source to destination mapping.
   */
  protected function buildSourceDestinationMapping(array $items) {
    $build = [];
    foreach ($items as $source => $destination) {
      $build[] = [
        'source' => ['#markup' => $source],
        'relationship' => ['#markup' => ($destination) ? ' → ' : ''],
        'destination' => [
          '#markup' => ($destination)
          ? (is_array($destination) ? implode(', ', $destination) : $destination)
          : '',
        ],
        '#prefix' => $build ? '<br/>' : '',
      ];
    }
    return ['data' => $build, 'nowrap' => TRUE];
  }

}
