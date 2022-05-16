<?php

namespace Drupal\schemadotorg\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Select entities using the field's mapping Schema.org property.
 *
 * This is a light-weight version of the DefaultSelection plugin.
 *
 * In theory, this selection plugin could support entity auto creation.
 *
 * The ::createNewEntity method is provided for devel generate.
 *
 * The 'entity_˚types' are set via schemadotorg_entity_reference_selection_alter.
 *
 * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
 * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::generateSampleValue
 * @see schemadotorg_entity_reference_selection_alter()
 *
 * @EntityReferenceSelection(
 *   id = "schemadotorg_range_includes",
 *   label = @Translation("Scheme.org range includes"),
 *   group = "schemadotorg_range_includes",
 *   entity_types = {},
 *   weight = 0
 * )
 *
 * @see schemadotorg_entity_reference_selection_alter()
 */
class SchemaDotOrgRangeIncludesSelection extends SelectionPluginBase implements ContainerFactoryPluginInterface {

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
      // Set 'auto_create = FALSE' to allow devel generate to work as expected.
      'auto_create' => FALSE,
      'auto_create_bundle' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Set the schemadotorg_mapping information in configuration based
    // on the current route's 'field_config' parameter.
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $this->routeMatch->getParameter('field_config');
    if ($field_config instanceof FieldConfigInterface) {
      $configuration = $this->getConfiguration();
      $configuration['schemadotorg_mapping'] = [
        'entity_type' => $field_config->getTargetEntityTypeId(),
        'bundle' => $field_config->getTargetBundle(),
        'field_name' => $field_config->getName()
      ];
      $this->setConfiguration($configuration);
    }

    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'] ?? 'node';
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type->getBundleEntityType());
    $target_entity_type_storage = $this->entityTypeManager->getStorage($entity_type->getBundleEntityType());

    $target_mappings = $this->getSchemaPropertyTargetMappings();
    if ($target_mappings) {
      // Get a list containing linked target bundles.
      $target_bundles_items = [];
      foreach ($target_mappings as $target_mapping) {
        $target_bundle = $target_entity_type_storage->load($target_mapping->getTargetBundle());
        $target_bundles_items[] = $this->t('@label (@type)', [
          '@label' => $target_bundle->label(),
          '@type' => $target_mapping->getSchemaType(),
        ]);
      }

      // Display message about entity reference selection.
      $t_args = [
        '@entity_types' => $bundle_entity_type->getPluralLabel(),
        '@property' => $this->getSchemaPropertyName(),
      ];
      $form['message'] = [
        '#markup' => '<p>' . $this->t("The below @entity_types will be automatically be available based this field's associated Schema.org property (@property).", $t_args) . '</p>',
      ];
      $form['bundles'] = [
        '#theme' => 'item_list',
        '#items' => $target_bundles_items,
      ];
    }
    elseif ($this->getSchemaPropertyName()) {
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
    else {
      // Display message that the field is not mapped.
      $t_args = [
        '@entity_type' => $bundle_entity_type->getSingularLabel(),
      ];
      $form['message'] = [
        '#markup' => '<p>' . $this->t('This field is not mapped to a Schema.org property.') . '</p>'
        . '<p><strong>' . $this->t("Please update this @entity_type's Schema.org type mapping.", $t_args) . '</strong></p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Since we are dynamically defining the handle settings we need to make
    // sure they are applied when the form is submitted.
    // @see schemadotorg_field_config_load()
    // @see \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::alterFieldValues
    // @see \Drupal\schemadotorg\Plugin\EntityReferenceSelection\SchemaDotOrgSelectionBase::buildConfigurationForm
    $settings = $form_state->getValue('settings');
    $settings += ['handler_settings' => []];
    $settings['handler_settings'] += $this->getConfiguration();
    $form_state->setValue('settings', $settings);
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
      // Never included unpublished terms (a.k.a. types and enumerations).
      if ($entity->isPublished()) {
        $bundle = $entity->bundle();
        $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label() ?? '');
      }
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

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Limit entity by the field Schema.org property's range includes converted
    // to target bundles.
    $target_bundles = $this->getSchemaPropertyTargetBundles();
    if ($target_bundles) {
      $bundle_key = $entity_type->getKey('bundle') ?? 'bundle';
      $query->condition($bundle_key, $target_bundles, 'IN');
    }

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
   * Gets the Schema.org property's range includes Schema.org types.
   *
   * @return array
   *   The Schema.org property's range includes Schema.org types.
   */
  protected function getSchemaPropertyRangeIncludes() {
    $mapping = $this->configuration['schemadotorg_mapping'];
    if (!$mapping['entity_type']) {
      return [];
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $schemadotorg_mapping_storage */
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $schemadotorg_mapping_storage->getSchemaPropertyRangeIncludes(
      $mapping['entity_type'],
      $mapping['bundle'],
      $mapping['field_name']
    );
  }

  /**
   * Gets the Schema.org property name for an entity field mapping.
   *
   * @return string
   *   The Schema.org property name for an entity field mapping.
   */
  protected function getSchemaPropertyName() {
    $mapping = $this->configuration['schemadotorg_mapping'];
    if (!$mapping['entity_type']) {
      return NULL;
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $schemadotorg_mapping_storage */
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $schemadotorg_mapping_storage->getSchemaPropertyName(
      $mapping['entity_type'],
      $mapping['bundle'],
      $mapping['field_name']
    );
  }

  /**
   * Gets the Schema.org mapping.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   The Schema.org mapping.
   */
  protected function getSchemaMapping() {
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    $mapping = $this->configuration['schemadotorg_mapping'];
    return $schemadotorg_mapping_storage->load($mapping['entity_type'] . '.' . $mapping['bundle']);
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $values = [
      $entity_type->getKey('label') => $label,
    ];

    if ($bundle_key = $entity_type->getKey('bundle')) {
      $values[$bundle_key] = $bundle;
    }

    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
  }

  /**
   * Gets the Schema.org property target bundles.
   *
   * @return array
   *   The Schema.org property target bundles.
   */
  protected function getSchemaPropertyTargetBundles() {
    $mapping = $this->configuration['schemadotorg_mapping'];
    if (!$mapping['entity_type']) {
      return [];
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $schemadotorg_mapping_storage */
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $schemadotorg_mapping_storage->getSchemaPropertyTargetBundles(
      $mapping['entity_type'],
      $mapping['bundle'],
      $mapping['field_name'],
      $this->configuration['target_type']
    );
  }

  /**
   * Gets the Schema.org property target mappings.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface[]
   *   The Schema.org property target mappings.
   */
  protected function getSchemaPropertyTargetMappings() {
    $mapping = $this->configuration['schemadotorg_mapping'];
    if (!$mapping['entity_type']) {
      return [];
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $schemadotorg_mapping_storage */
    $schemadotorg_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $schemadotorg_mapping_storage->getSchemaPropertyTargetMappings(
      $mapping['entity_type'],
      $mapping['bundle'],
      $mapping['field_name'],
      $this->configuration['target_type']
    );
  }

}
