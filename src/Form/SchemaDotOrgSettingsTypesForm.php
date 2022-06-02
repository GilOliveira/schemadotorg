<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org types settings for this site.
 */
class SchemaDotOrgSettingsTypesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_types_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg.settings');

    $form['schema_types'] = [
      '#tree' => TRUE,
    ];
    $form['schema_types']['main_entities'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'SchemaType|MainEntity',
      '#title' => $this->t('Schema.org types main entity'),
      '#description' => $this->t('Enter the main entity for a Schema.org type.'),
      '#default_value' => $config->get('schema_types.main_entities'),
    ];
    $form['schema_types']['main_properties'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'SchemaType|propertyName',
      '#title' => $this->t('Schema.org types main property'),
      '#description' => $this->t('Enter the main property for a Schema.org type. Defaults to <em>name</em> for unspecified Schema.org types.'),
      '#default_value' => $config->get('schema_types.main_properties'),
    ];
    $form['schema_types']['default_field_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'SchemaType|field_type_01,field_type_02,field_type_03',
      '#title' => $this->t('Default Schema.org type field types'),
      '#description' => $this->t('Enter the field types applied to a Schema.org type when a property is added to an entity type.')
      . ' '
      . $this->t('Field types are applied in the order that they are entered.'),
      '#default_value' => $config->get('schema_types.default_field_types'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg.settings')
      ->set('schema_types', $form_state->getValue('schema_types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
