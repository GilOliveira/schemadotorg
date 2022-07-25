<?php

namespace Drupal\schemadotorg_subtype\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org subtype settings.
 */
class SchemaDotOrgSubtypeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_subtype_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_subtype.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_subtype.settings');
    $form['default_field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default subtype field suffix'),
      '#description' => $this->t('Enter default field suffix used for subtype field machine names.'),
      '#default_value' => $config->get('default_field_suffix'),
    ];
    $form['default_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default subtype field label'),
      '#description' => $this->t('Enter default label used for subtype fields.'),
      '#required' => TRUE,
      '#default_value' => $config->get('default_field_label'),
    ];
    $form['default_field_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default subtype field description'),
      '#description' => $this->t('Enter default description used for subtype fields.'),
      '#default_value' => $config->get('default_field_description'),
    ];
    $form['default_subtypes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#settings_format' => 'SchemaType',
      '#title' => $this->t('Default subtypes'),
      '#description' => $this->t('Enter Schema.org types that support subtyping by default.'),
      '#description_link' => 'subtypes',
      '#default_value' => $config->get('default_subtypes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg_subtype.settings')
      ->set('default_field_suffix', $form_state->getValue('default_field_suffix'))
      ->set('default_field_label', $form_state->getValue('default_field_label'))
      ->set('default_field_description', $form_state->getValue('default_field_description'))
      ->set('default_subtypes', $form_state->getValue('default_subtypes'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
