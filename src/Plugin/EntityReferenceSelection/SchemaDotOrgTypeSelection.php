<?php

namespace Drupal\schemadotorg\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;

/**
 * Select entities using the field's mapping Schema.org property.
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
 * @see schemadotorg_entity_reference_selection_alter()
 */
class SchemaDotOrgTypeSelection extends SchemaDotOrgSelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

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
        $target_bundles_items[] = $target_bundle
          ->toLink($target_bundle->label(), 'add-form')
          ->toRenderable() + ['#suffix' => ' (' . $target_mapping->getSchemaType() . ')'];
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
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($target_type);

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
   * Get the Schema.org property target mappings.
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
