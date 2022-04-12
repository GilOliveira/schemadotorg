<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schema.org mapping type form.
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $entity
 */
class SchemaDotOrgMappingTypeForm extends EntityForm {
  use SchemaDotOrgFormTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $entity */
    $entity = $this->getEntity();

    if ($entity->isNew()) {
      $form['target_entity_type_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Target entity type'),
        '#options' => $this->getTargetEntityTypeOptions(),
        '#required' => TRUE,
      ];
    }
    else {
      $form['target_entity_type'] = [
        '#type' => 'item',
        '#title' => $this->t('Target entity type'),
        '#value' => $entity->id(),
        '#markup' => $entity->label(),
      ];
    }
    $form['recommended_schema_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Recommended Schema.org types'),
      '#description' => $this->t('Enter one value per line, in the format group_name|group_label|SchemaType01,SchemaType01,SchemaType01.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->groupedTypesListString($entity->get('recommended_schema_types')),
      '#element_validate' => ['::validateGroupedTypesList'],
    ];
    $form['default_schema_types'] = [
      '#type' => 'textarea',
      '#title' => 'Default Schema.org types',
      '#description' => $this->t('Enter one value per line, in the format entity_type|schema_type.'),
      '#default_value' => $this->keyValuesString($entity->get('default_schema_types')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['default_schema_type_properties'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type properties'),
      '#description' => $this->t('Enter one value per line, in the format SchemaType|propertyName01,propertyName02,propertyName02.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->nestedListString($entity->get('default_schema_type_properties')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['default_schema_type_subtypes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type subtyping'),
      '#description' => $this->t('Enter one Schema.org type per line.'),
      '#default_value' => $this->listString($entity->get('default_schema_type_subtypes')),
      '#element_validate' => ['::validateList'],
    ];
    $form['default_base_fields'] = [
      '#type' => 'textarea',
      '#title' => 'Default base field mappings',
      '#description' => $this->t('Enter one value per line, in the format base_field_name|property_name_01,property_name_02')
      . '<br/>' . $this->t('The property_name value be left blank if you want the base field available but not mapped to a Schema.org property.'),
      '#default_value' => $this->nestedListString($entity->get('default_base_fields')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['default_field_groups'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default field groups'),
      '#description' => $this->t('Enter one value per line, in the format group_name|group_label|property01,property02,property03.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->groupedPropertiesListString($entity->get('default_field_groups')),
      '#element_validate' => ['::validateGroupedPropertiesList'],
    ];
    $type_options = [
      'details' => $this->t('Details'),
      'html_element' => $this->t('HTML element'),
      'fieldset' => $this->t('Fieldset'),
    ];
    $form['default_field_group_form_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default field group form type'),
      '#options' => $type_options,
      '#default_value' => $entity->get('default_field_group_form_type'),
      '#empty_value' => '',
      '#empty_option' => $this->t('- None -'),
    ];
    $form['default_field_group_view_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default field group view type'),
      '#options' => $type_options,
      '#default_value' => $entity->get('default_field_group_view_type'),
      '#empty_value' => '',
      '#empty_option' => $this->t('- None -'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $t_args = ['%label' => $this->getEntity()->label()];
    $message = ($result === SAVED_NEW)
      ? $this->t('Created %label mapping type.', $t_args)
      : $this->t('Updated %label mapping type.', $t_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

  /* ************************************************************************ */
  // Options.
  /* ************************************************************************ */

  /**
   * Get available target content entity type options.
   *
   * @return array
   *   Available target content entity type options.
   */
  protected function getTargetEntityTypeOptions() {
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $definitions = $this->entityTypeManager->getDefinitions();

    $options = [];
    foreach ($definitions as $definition) {
      if ($definition instanceof ContentEntityTypeInterface
        && !$mapping_type_storage->load($definition->id())) {
        $options[$definition->id()] = $definition->getLabel();
      }
    }
    return $options;
  }

}
