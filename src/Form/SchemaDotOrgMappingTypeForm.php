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
      '#description' => $this->t('Enter recommended Schema.org types to be displayed when creating a new Schema.org type. Recommended Schema.org types will only be displayed on entity types that support adding new Schema.org types.')
      . ' '
      . $this->t('Enter one value per line, in the format <code>group_name|group_label|SchemaType01,SchemaType01,SchemaType01</code>.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->groupedTypesListString($entity->get('recommended_schema_types')),
      '#element_validate' => ['::validateGroupedTypesList'],
    ];
    $form['default_schema_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org types'),
      '#description' => $this->t('Enter default Schema.org types that will automatically be assigned to an existing entity type/bundle.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the <code>format entity_type|schema_type</code>.'),
      '#default_value' => $this->keyValuesString($entity->get('default_schema_types')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['default_schema_type_properties'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type properties'),
      '#description' => $this->t('Enter default Schema.org type properties that are used when a Schema.org type mapping is being created or added to an entity.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>SchemaType|propertyName01,propertyName02,propertyName02</code>.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->nestedListString($entity->get('default_schema_type_properties')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['default_schema_type_subtypes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type subtyping'),
      '#description' => $this->t('Enter default Schema.org type subtyping, which is used to enable subtyping when a Schema.org type is being created automatically.')
      . '<br/><br/>'
      . $this->t('Enter one Schema.org type per line.'),
      '#default_value' => $this->listString($entity->get('default_schema_type_subtypes')),
      '#element_validate' => ['::validateList'],
    ];
    $form['default_base_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default base field mappings'),
      '#description' => $this->t('Enter default base field mappings from existing entity properties and fields to Schema.org properties.')
      . ' ' . $this->t('Leave the property_name value blank to allow the base field to be available but not mapped to a Schema.org property.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>base_field_name|property_name_01,property_name_02</code>.'),
      '#default_value' => $this->nestedListString($entity->get('default_base_fields')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['default_field_weights'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default field weights'),
      '#description' => $this->t('Enter Schema.org property default field weights to help org Schema.org as they are added to entity types.')
      . '<br/><br/>'
      . $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($entity->get('default_field_weights')),
      '#element_validate' => ['::validateList'],
    ];
    $form['default_field_groups'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default field groups'),
      '#description' => $this->t('Enter the default field groups and field order used to group Schema.org properties as they are added to entity types.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>group_name|group_label|property01,property02,property03</code>.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->groupedPropertiesListString($entity->get('default_field_groups')),
      '#element_validate' => ['::validateGroupedPropertiesList'],
    ];
    $type_options = [
      'details' => $this->t('Details'),
      'html_element' => $this->t('HTML element'),
      'fieldset' => $this->t('Fieldset'),
    ];
    $form['default_field_group_label_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default field group label suffix'),
      '#description' => $this->t('Enter the field group label suffix used when creating new field groups.'),
      '#default_value' => $entity->get('default_field_group_label_suffix'),
    ];
    $form['default_field_group_form_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default field group form type'),
      '#description' => $this->t("Select the default field group type used when adding a field group to a entity type's default form."),
      '#options' => $type_options,
      '#default_value' => $entity->get('default_field_group_form_type'),
      '#empty_value' => '',
      '#empty_option' => $this->t('- None -'),
    ];
    $form['default_field_group_view_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default field group view type'),
      '#description' => $this->t("Select the default field group type used when adding a field group to a entity type's default view display."),
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
