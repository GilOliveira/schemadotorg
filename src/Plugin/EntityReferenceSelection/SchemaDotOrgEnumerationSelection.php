<?php

namespace Drupal\schemadotorg\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;

/**
 * Select entities using the field's mapping Schema.org property.
 *
 * @EntityReferenceSelection(
 *   id = "schemadotorg_enumeration",
 *   label = @Translation("Scheme.org enumeration"),
 *   group = "schemadotorg_enumeration",
 *   entity_types = {"taxonomy_term"},
 *   weight = 0
 * )
 */
class SchemaDotOrgEnumerationSelection extends SchemaDotOrgSelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'] ?? 'taxonomy_term';
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $tids = $this->getSchemaEnumerationTermIds();
    if ($tids) {
      $entity_type_storage = $this->entityTypeManager->getStorage($entity_type_id);
      $terms = $entity_type_storage->loadMultiple($tids);
      $labels = [];
      foreach ($terms as $term) {
        $labels[] = $term->label();
      }
      $t_args = [
        '@entity_types' => $entity_type->getCollectionLabel(),
        '@terms' => implode(', ', $labels),
      ];
      $form['message'] = [
        '#markup' => '<p>' . $this->t("@entity_types (@terms) will be automatically available based this field's associated Schema.org property enumeration.", $t_args) . '</p>',
      ];
    }
    else {
      $t_args = [
        '@entity_type' => $entity_type->getSingularLabel(),
      ];
      $form['message'] = [
        '#markup' => '<p>' . $this->t('This field is not mapped to a Schema.org property enumeration.') . '</p>'
        . '<p><strong>' . $this->t("Please update this @entity_type's Schema.org type mapping.", $t_args) . '</strong></p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $tids = $this->getSchemaEnumerationTermIds();
    if ($tids) {
      $query->condition('parent', $tids, 'IN');
    }
    return $query;
  }

  /**
   * Gets Schema.org enumeration term ids.
   *
   * @return array|int
   *   Schema.org enumeration term ids.
   */
  protected function getSchemaEnumerationTermIds() {
    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'] ?? 'taxonomy_term';

    $range_includes = $this->getSchemaPropertyRangeIncludes();
    return $range_includes ? $this->entityTypeManager->getStorage($entity_type_id)
      ->getQuery()
      ->condition('vid', 'schema_enumeration')
      ->condition('schema_type', $range_includes, 'IN')
      ->execute() : [];
  }

}
