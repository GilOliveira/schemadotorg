<?php

namespace Drupal\schemadotorg\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Select entities using the field's mapping Schema.org property.
 *
 * This is a light-weight version of the DefaultSelection plugin.
 *
 * In theory, this selection plugin could support entity auto creation.
 *
 * The 'entity_types' are set via schemadotorg_entity_reference_selection_alter.
 *
 * @EntityReferenceSelection(
 *   id = "schemadotorg_type",
 *   label = @Translation("Scheme.org type"),
 *   group = "schemadotorg_type",
 *   entity_types = {},
 *   weight = 0
 * )
 *
 * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
 */
class SchemaDotOrgTypeSelection extends SelectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  public $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Schema.org mapping info is set via schemadotorg_field_config_presave().
      'schemadotorg_mapping' => [
        'entity_type' => NULL,
        'bundle' => NULL,
        'field_name' => NULL,
      ],
    ] + parent::defaultConfiguration();
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type->getBundleEntityType());

    // Set the schemadotorg_mapping information in configuration based
    // on the current route's 'field_config' parameter.
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $this->routeMatch->getParameter('field_config');
    if ($field_config instanceof FieldConfigInterface) {
      $configuration['schemadotorg_mapping'] = [
        'entity_type' => $field_config->getTargetEntityTypeId(),
        'bundle' => $field_config->getTargetBundle(),
        'field_name' => $field_config->getName()
      ];
      $this->setConfiguration($configuration);
    }

    $target_bundles = $this->getSchemaPropertyTargetBundles() ;
    if ($target_bundles) {
      // Get the target bundle labels.
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($target_bundles as $target_bundle) {
        $target_bundles[$target_bundle] = $bundles[$target_bundle]['label'];
      }
      // Display message about entity reference selection.
      $t_args = [
        '@entity_types' => $bundle_entity_type->getCollectionLabel(),
        '@bundles' => implode(', ', $target_bundles),
      ];
      $form['message'] = [
        '#markup' => '<p>' . $this->t("@entity_types (@bundles) will be automatically available based this field's associated Schema.org property.", $t_args) . '</p>',
      ];
    }
    else {
      // Display message about broken entity reference selection.
      $t_args = [
        '@entity_types' => $bundle_entity_type->getPluralLabel(),
        '@entity_type' => $bundle_entity_type->getSingularLabel(),
        '@range_includes' => implode(', ', $this->getSchemaPropertyRangeIncludes()),
      ];
      $form['message'] = [
        '#markup' => '<p>' . $this->t("There are no @entity_types that will be automatically available based this field's associated Schema.org property.", $t_args) . '</p>'
        . '<p><strong>' . $this->t('Please create a new @entity_type and map it to one of the following Schema.org types (@range_includes).', $t_args) . '</strong></p>',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label() ?? '');
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->buildEntityQuery($match, $match_operator);
    return $query
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $query = $this->buildEntityQuery();
      $result = $query
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }

    return $result;
  }

  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($target_type);

    $query = $this->entityTypeManager->getStorage($target_type)->getQuery();
    $query->accessCheck(TRUE);

    // Limit entity by the field Schema.org property's range includes converted
    // to target bundles.
    $target_bundles = $this->getSchemaPropertyTargetBundles() ;
    if ($target_bundles) {
      $bundle_key = $entity_type->getKey('bundle') ?? 'bundle';
      $query->condition($bundle_key, $target_bundles, 'IN');
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    return $query;
  }

  /**
   * Helper method: Passes a query to the alteration system again.
   *
   * This allows Entity Reference to add a tag to an existing query so it can
   * ask access control mechanisms to alter it again.
   */
  protected function reAlterQuery(AlterableInterface $query, $tag, $base_table) {
    // Save the old tags and metadata.
    // For some reason, those are public.
    $old_tags = $query->alterTags;
    $old_metadata = $query->alterMetaData;

    $query->alterTags = [$tag => TRUE];
    $query->alterMetaData['base_table'] = $base_table;
    $this->moduleHandler->alter(['query', 'query_' . $tag], $query);

    // Restore the tags and metadata.
    $query->alterTags = $old_tags;
    $query->alterMetaData = $old_metadata;
  }

  /**
   * Get the Schema.org property target bundles.
   *
   * @return array
   *   The Schema.org property target bundles.
   */
  protected function getSchemaPropertyTargetBundles() {
    $mapping = $this->configuration['schemadotorg_mapping'];
    if (!$mapping['entity_type']) {
      return [];
    }

    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $schemadotorg_mapping_storage->getSchemaPropertyTargetBundles(
      $mapping['entity_type'],
      $mapping['bundle'],
      $mapping['field_name'],
      $this->configuration['target_type']
    );
  }

  /**
   * Get the Schema.org property's range includes Schema.org types.
   *
   * @return array
   *   The Schema.org property's range includes Schema.org types.
   */
  protected function getSchemaPropertyRangeIncludes() {
    $mapping = $this->configuration['schemadotorg_mapping'];
    if (!$mapping['entity_type']) {
      return [];
    }

    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $schemadotorg_mapping_storage->getSchemaPropertyRangeIncludes(
      $mapping['entity_type'],
      $mapping['bundle'],
      $mapping['field_name']
    );
  }
}