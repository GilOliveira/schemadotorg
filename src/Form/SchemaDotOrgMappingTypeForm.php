<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Schema.org mapping type form.
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $entity
 */
class SchemaDotOrgMappingTypeForm extends EntityForm {

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
      // Display a warning about the missing entity type.
      if (!$this->entityTypeManager->hasDefinition($entity->id())) {
        $t_args = ['%entity_type' => $entity->id()];
        $message = $this->t('The target entity type %entity_type is missing and its associated module most likely needs to be installed.', $t_args);
        $this->messenger()->addWarning($message);
      }
    }
    $form['recommended_schema_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#settings_format' => 'group_name|group_label|SchemaType01,SchemaType01,SchemaType01',
      '#array_name' => 'types',
      '#title' => $this->t('Recommended Schema.org types'),
      '#description' => $this->t('Enter recommended Schema.org types to be displayed when creating a new Schema.org type. Recommended Schema.org types will only be displayed on entity types that support adding new Schema.org types.'),
      '#default_value' => $entity->get('recommended_schema_types'),
    ];
    $form['default_schema_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'format entity_type|schema_type',
      '#title' => $this->t('Default Schema.org types'),
      '#description' => $this->t('Enter default Schema.org types that will automatically be assigned to an existing entity type/bundle.'),
      '#default_value' => $entity->get('default_schema_types'),
    ];
    $form['default_schema_type_properties'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'SchemaType|propertyName01,propertyName02,propertyName02',
      '#title' => $this->t('Default Schema.org type properties'),
      '#default_value' => $entity->get('default_schema_type_properties'),
    ];
    $form['default_schema_type_subtypes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_types' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Default Schema.org type subtyping'),
      '#description' => $this->t('Enter default Schema.org type subtyping, which is used to enable subtyping when a Schema.org type is being created automatically.'),
      '#default_value' => $entity->get('default_schema_type_subtypes'),
    ];
    $form['default_base_fields'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'base_field_name|property_name_01,property_name_02',
      '#title' => $this->t('Default base field mappings'),
      '#description' => $this->t('Enter default base field mappings from existing entity properties and fields to Schema.org properties.')
      . ' ' . $this->t('Leave the property_name value blank to allow the base field to be available but not mapped to a Schema.org property.'),
      '#default_value' => $entity->get('default_base_fields'),
    ];
    $form['default_field_weights'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_types' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Default field weights'),
      '#description' => $this->t('Enter Schema.org property default field weights to help org Schema.org as they are added to entity types.'),
      '#default_value' => $entity->get('default_field_weights'),
    ];
    $form['default_field_groups'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#settings_format' => 'group_name|group_label|property01,property02,property03',
      '#array_name' => 'properties',
      '#title' => $this->t('Default field groups'),
      '#description' => $this->t('Enter the default field groups and field order used to group Schema.org properties as they are added to entity types.'),
      '#default_value' => $entity->get('default_field_groups'),
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
