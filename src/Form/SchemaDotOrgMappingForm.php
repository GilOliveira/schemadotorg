<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Schema.org mapping form.
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity
 */
class SchemaDotOrgMappingForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    $entity = $this->getEntity();

    $form['#title'] = $this->t('Schema.org mapping');

    $target_entity_type_definition = $entity->getTargetEntityTypeDefinition();
    $target_entity_type_bundle_definition = $entity->getTargetEntityTypeBundleDefinition();
    $form['entity_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => $target_entity_type_bundle_definition
      ? $target_entity_type_bundle_definition->getLabel()
      : $target_entity_type_definition->getLabel(),
    ];

    $entity_type_bundle = $entity->getTargetEntityBundleEntity();
    if ($entity_type_bundle) {
      $form['bundle_name'] = [
        '#type' => 'item',
        '#title' => $this->t('Name'),
        '#markup' => $entity_type_bundle->label(),
      ];
      $form['bundle_id'] = [
        '#type' => 'item',
        '#title' => $this->t('ID'),
        '#markup' => $entity_type_bundle->id(),
      ];
    }

    $form['schema_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Schema.org type'),
      '#markup' => $entity->getSchemaType(),
    ];

    $schema_properties = $entity->getSchemaProperties();
    if ($schema_properties) {
      $header = [
        $this->t('Field name'),
        $this->t('Schema.org property'),
      ];
      $rows = [];
      foreach ($schema_properties as $field_name => $mapping) {
        $rows[] = [
          $field_name,
          $mapping['property'],
        ];
      }
      $form['schema_properties'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    return [];
  }

}
