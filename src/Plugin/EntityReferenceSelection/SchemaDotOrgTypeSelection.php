<?php

namespace Drupal\schemadotorg\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Select entities using the field's mapping Schema.org property.
 *
 * @EntityReferenceSelection(
 *   id = "schemadotorg_type",
 *   label = @Translation("Scheme.org type"),
 *   group = "schemadotorg_type",
 *   entity_types = {"taxonomy_term"},
 *   weight = 0
 * )
 *
 * @see \Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection
 */
class SchemaDotOrgTypeSelection extends SchemaDotOrgSelectionBase {

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['depth' => 1] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $tids = $this->getSchemaTypeTermIds();
    if ($tids) {
      $schema_type = $this->getSchemaMapping()->getSchemaType();
      // Display message about the Schema.org subtypes.
      $t_args = ['@type' => $schema_type];
      $form['message'] = [
        '#markup' => '<p>' . $this->t('Subtypes will automatically available based the Schema.org type (@type).', $t_args) . '</p>',
      ];
      $form['depth'] = [
        '#type' => 'select',
        '#title' => $this->t('Depth'),
        '#description' => $this->t('The depth will match subtypes terms in the type hierarchy.'),
        '#options' => range(0, 10),
        '#default_value' => $this->configuration['depth'],
      ];
      $tree = $this->schemaTypeManager->getTypeTree($schema_type);
      $form['tree'] = [
        '#type' => 'details',
        '#title' => $this->t('More specific Schema.org subtypes'),
        'items' => $this->schemaTypeBuilder->buildTypeTree($tree),
      ];
      $form['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';
    }
    elseif ($this->getSchemaMapping()) {
      // Display message when the Schema.org type has no subtypes.
      $t_args = ['@type' => $this->getSchemaMapping()->getSchemaType()];
      $form['message'] = [
        '#markup' => '<p>' . $this->t('The Schema.org type (@type) has no subtypes.', $t_args) . '</p>',
      ];
    }
    else {
      // Display message when the field's entity is not mapped to a Schema.org type.
      $form['message'] = [
        '#markup' => '<p>' . $this->t("This field's entity is not mapped to a Schema.org type.") . '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    $options = [];
    $terms = $this->getChildTerms(TRUE);
    foreach ($terms as $term) {
      // Never included unpublished terms (a.k.a. types and enumerations).
      if ($term->isPublished()) {
        $options['schema_thing'][$term->id()] = str_repeat('-', $term->depth) . Html::escape($this->entityRepository->getTranslationFromContext($term)->label());
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $tids = $this->getSchemaTypeTermIds();
    if ($tids) {
      $query->condition('tid', $tids, 'IN');
    }
    return $query;
  }

  /**
   * Gets Schema.org enumeration term ids.
   *
   * @return array|int
   *   Schema.org enumeration term ids.
   */
  protected function getSchemaTypeTermIds() {
    $children = $this->getChildTerms();
    $child_tids = [];
    foreach ($children as $child) {
      $child_tids[$child->tid] = $child->tid;
    }
    return $child_tids;
  }

  /**
   * Get Schema.org type's child terms.
   *
   * @param bool $load_entities
   *   If TRUE, a full entity load will occur on the term objects.
   *
   * @return array|\Drupal\taxonomy\TermInterface[]
   *   Schema.org type's child terms.
   */
  protected function getChildTerms($load_entities = FALSE) {
    $schema_mapping = $this->getSchemaMapping();
    if (!$schema_mapping) {
      return [];
    }

    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'] ?? 'taxonomy_term';
    $term_storage = $this->entityTypeManager->getStorage($entity_type_id);

    $tids = $term_storage->getQuery()
      ->condition('vid', 'schema_thing')
      ->condition('schema_type', $schema_mapping->getSchemaType())
      ->execute();
    if (!$tids) {
      return [];
    }

    $parent = reset($tids);
    $depth = $configuration['depth'] ?: NULL;
    return $term_storage->loadTree('schema_thing', $parent, $depth, $load_entities);
  }

}
