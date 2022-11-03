<?php

declare(strict_types = 1);

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
  public function getFormId(): string {
    return 'schemadotorg_subtype_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_subtype.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('schemadotorg_subtype.settings');
    $form['schemadotorg_subtype'] = [
      '#type' => 'details',
      '#title' => $this->t('Subtype settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_subtype']['default_field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default subtype field suffix'),
      '#description' => $this->t('Enter default field suffix used for subtype field machine names.'),
      '#default_value' => $config->get('default_field_suffix'),
    ];
    $form['schemadotorg_subtype']['default_field_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default subtype field label'),
      '#description' => $this->t('Enter default label used for subtype fields.'),
      '#required' => TRUE,
      '#default_value' => $config->get('default_field_label'),
    ];
    $form['schemadotorg_subtype']['default_field_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default subtype field description'),
      '#description' => $this->t('Enter the default description used for subtype fields.'),
      '#default_value' => $config->get('default_field_description'),
    ];
    $form['schemadotorg_subtype']['default_subtypes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#settings_format' => 'SchemaType',
      '#title' => $this->t('Default subtypes'),
      '#description' => $this->t('Enter Schema.org types that support subtyping by default.'),
      '#description_link' => 'subtypes',
      '#default_value' => $config->get('default_subtypes'),
    ];
    $form['schemadotorg_subtype']['default_allowed_values'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED,
      '#settings_format' => 'SchemaType|prSchemaSubtype01:Subtype 01,SchemaSubtype02:Subtype 02,SchemaSubtype03:Subtype 03',
      '#title' => $this->t('Schema.org default subtypes allowed values'),
      '#description' => $this->t('Enter default subtype allowed values for Schema.org types.'),
      '#description_link' => 'types',
      '#default_value' => $config->get('default_allowed_values'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('schemadotorg_subtype.settings');
    $values = $form_state->getValue('schemadotorg_subtype');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
