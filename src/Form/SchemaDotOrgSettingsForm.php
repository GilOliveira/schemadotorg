<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Schema.org settings for this site.
 */
class SchemaDotOrgSettingsForm extends ConfigFormBase {
  use SchemaDotOrgFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_settings';
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

    $form['#tree'] = TRUE;

    $form['schema_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org types'),
      '#open' => TRUE,
    ];
    $form['schema_types']['default_field_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type field types'),
      '#description' => $this->t('Enter one value per line, in the format SchemaType|propertyName01,propertyName02,propertyName02.'),
      '#default_value' => $this->nestedListString($config->get('schema_types.default_field_types')),
      '#element_validate' => ['::validateNestedList'],
    ];

    $form['schema_properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org properties'),
      '#open' => TRUE,
    ];
    $form['schema_properties']['default_field_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org property field types'),
      '#description' => $this->t('Enter one value per line, in the format propertyName|field_type_01,field_type_02,field_type_03.'),
      '#default_value' => $this->nestedListString($config->get('schema_properties.default_field_types')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['schema_properties']['default_unlimited_fields'] = [
      '#type' => 'textarea',
      '#title' => 'Default unlimited Schema.org properties',
      '#description' => $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($config->get('schema_properties.default_unlimited_fields')),
      '#element_validate' => ['::validateList'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg.settings')
      ->set('schema_types', $form_state->getValue('schema_types'))
      ->set('schema_properties', $form_state->getValue('schema_properties'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
