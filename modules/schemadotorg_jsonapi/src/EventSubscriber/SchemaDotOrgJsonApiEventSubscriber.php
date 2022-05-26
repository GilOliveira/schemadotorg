<?php

namespace Drupal\schemadotorg_jsonapi\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alters Schema.org mapping list builder and adds a 'JSON:API' column.
 *
 * @see \Drupal\schemadotorg\SchemaDotOrgMappingListBuilder
 */
class SchemaDotOrgJsonApiEventSubscriber extends ServiceProviderBase implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The JSON:API configurable resource type repository.
   *
   * @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * Constructs an SchemaDotOrgJsonApiEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository $resource_type_respository
   *   The JSON:API configurable resource type repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, ConfigurableResourceTypeRepository $resource_type_respository) {
    $this->configFactory = $config_factory;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->resourceTypeRepository = $resource_type_respository;
  }

  /**
   * Alters Schema.org mapping list builder and adds a 'JSON:API' column.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onView(ViewEvent $event) {
    if ($this->routeMatch->getRouteName() !== 'entity.schemadotorg_mapping.collection') {
      return;
    }

    $result = $event->getControllerResult();

    // Header.
    // Add 'JSON:API' to header after 'Name'.
    // @see \Drupal\schemadotorg\SchemaDotOrgMappingTypeListBuilder::buildHeader
    $details_toggle = (boolean) $event->getRequest()->query->get('details') ?? 0;
    $header_width = $details_toggle ? '10%' : '27%';
    $header =& $result['table']['#header'];
    $header['bundle_label']['width'] = $header_width;
    $header['schema_type']['width'] = $header_width;
    $header_cell = [
      'data' => $this->t('JSON:API'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'width' => $header_width,
    ];
    $this->insertAfter($header, 'bundle_label', 'jsonapi', $header_cell);

    // Rows.
    // Add 'JSON:API' to row after 'Name'.
    // @see \Drupal\schemadotorg\SchemaDotOrgMappingTypeListBuilder::buildRow
    $path_prefix = $this->configFactory
      ->get('jsonapi_extras.settings')
      ->get('path_prefix');
    foreach ($result['table']['#rows'] as $id => &$row) {
      [$entity_type_id, $bundle] = explode('.', $id);

      $resource_id = "$entity_type_id--$bundle";
      $resource_type = $this->resourceTypeRepository->getByTypeName($resource_id);
      $resource_path = sprintf('/%s%s', $path_prefix, $resource_type->getPath());
      $resource_includes = $this->getResourceIncludes($resource_type);
      $resource_options = $resource_includes
      ? ['query' => ['include' => implode(',', $resource_includes)]]
      : [];
      $row_cell = [
        'data' => [
          '#type' => 'link',
          '#title' => $resource_path,
          '#url' => Url::fromUri('base:' . $resource_path, $resource_options),
          '#prefix' => '<code>',
          '#suffix' => '</code>',
        ],
      ];
      $this->insertAfter($row, 'bundle_label', 'jsonapi', $row_cell);
    }

    $event->setControllerResult($result);
  }

  /**
   * Get resource type's entity reference fields as an array of includes.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type.
   *
   * @return array
   *   An array of entity reference field public names to be used as includes.
   */
  protected function getResourceIncludes(ResourceType $resource_type) {
    $entity_type_id = $resource_type->getEntityTypeId();
    $bundle = $resource_type->getBundle();

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping')
      ->load("$entity_type_id.$bundle");

    $includes = [];

    $relationships = $resource_type->getRelatableResourceTypes();
    $field_names = array_keys($mapping->getAllSchemaProperties());
    foreach ($field_names as $field_name) {
      $field = $resource_type->getFieldByInternalName($field_name);
      if ($field) {
        $public_name = $field->getPublicName();
        if (isset($relationships[$public_name])) {
          $includes[] = $public_name;
        }
      }
    }

    return $includes;
  }

  /**
   * Inserts a new key/value after the key in the array.
   *
   * @param array &$array
   *   An array to insert in to.
   * @param string $target_key
   *   The key to insert after.
   * @param string $new_key
   *   The key to insert.
   * @param mixed $new_value
   *   A value to insert.
   */
  protected function insertAfter(array &$array, $target_key, $new_key, $new_value) {
    $new = [];
    foreach ($array as $key => $value) {
      $new[$key] = $value;
      if ($key === $target_key) {
        $new[$new_key] = $new_value;
      }
    }
    $array = $new;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before main_content_view_subscriber.
    $events[KernelEvents::VIEW][] = ['onView', 100];
    return $events;
  }

}
