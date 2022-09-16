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

    // Type settings.
    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Type settings'),
      '#open' => TRUE,
    ];
    if ($entity->isNew()) {
      $form['types']['target_entity_type_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Target entity type'),
        '#options' => $this->getTargetEntityTypeOptions(),
        '#required' => TRUE,
      ];
    }
    else {
      $form['types']['target_entity_type'] = [
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
    $form['types']['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple mappings to point to the same Schema.org type'),
      '#description' => $this->t('If unchecked, new mappings to an existing Schema.org type will display a warning'),
      '#return_value' => TRUE,
      '#default_value' => $entity->get('multiple'),
    ];
    $form['types']['recommended_schema_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#settings_format' => 'group_name|group_label|SchemaType01,SchemaType01,SchemaType01',
      '#array_name' => 'types',
      '#title' => $this->t('Recommended Schema.org types'),
      '#description' => $this->t('Enter recommended Schema.org types to be displayed when creating a new Schema.org type. Recommended Schema.org types will only be displayed on entity types that support adding new Schema.org types.'),
      '#description_link' => 'types',
      '#default_value' => $entity->get('recommended_schema_types'),
    ];
    $form['types']['default_schema_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'format entity_type|schema_type',
      '#title' => $this->t('Default Schema.org types'),
      '#description' => $this->t('Enter default Schema.org types that will automatically be assigned to an existing entity type/bundle.'),
      '#description_link' => 'types',
      '#default_value' => $entity->get('default_schema_types'),
    ];

    // Property settings.
    $form['properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Property settings'),
      '#open' => TRUE,
    ];
    $form['properties']['default_schema_type_properties'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'SchemaType|propertyName01,propertyName02,propertyName02',
      '#title' => $this->t('Default Schema.org type properties'),
      '#description' => $this->t('Enter default Schema.org type properties.')
      . '<br/><br/>'
      . $this->t('Please note: Default properties are automatically inherited from their parent Schema.org type and <a href="https://schema.org/Intangible">Intangible</a> are automatically assigned all defined properties, expect for properties defined via <a href="https://schema.org/Thing">Thing</a>.')
      . ' '
      . $this->t('Prepend a minus to a property to explicitly remove the property from the specific type.'),
      '#description_link' => 'types',
      '#default_value' => $entity->get('default_schema_type_properties'),
    ];
    $form['properties']['default_base_fields'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'base_field_name|property_name_01,property_name_02',
      '#title' => $this->t('Default base field to Schema.org property mappings'),
      '#description' => $this->t('Enter default base field mappings from existing entity properties and fields to Schema.org properties.')
      . ' ' . $this->t('Leave the property_name value blank to allow the base field to be available but not mapped to a Schema.org property.'),
      '#description_link' => 'properties',
      '#default_value' => $entity->get('default_base_fields'),
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
