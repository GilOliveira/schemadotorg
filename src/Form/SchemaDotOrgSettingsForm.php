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

    $form['schema_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org types'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $form['schema_types']['default_field_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type field types'),
      '#description' => $this->t('Enter one value per line, in the format format SchemaType|propertyName01,propertyName02,propertyName02.'),
      '#default_value' => $this->nestedListString($config->get('schema_types.default_field_types')),
      '#element_validate' => ['::validateNestedList'],
    ];

    $form['schema_properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org properties'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $form['schema_properties']['default_field_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org property field types'),
      '#description' => $this->t('Enter one value per line, in the format format propertyName|field_type_01,field_type_02,field_type_03.'),
      '#default_value' => $this->nestedListString($config->get('schema_properties.default_field_types')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['schema_properties']['default_unlimited_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default unlimited Schema.org properties'),
      '#description' => $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($config->get('schema_properties.default_unlimited_fields')),
      '#element_validate' => ['::validateList'],
    ];

    $form['names'] = [
      '#type' => 'details',
      '#title' => $this->t('Names'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $form['names']['message'] = [
      '#theme' => 'status_messages',
      '#message_list' => ['warning' => [$this->t('Adjusting prefixes, suffixes, and abbreviations can impact existing Schema.org mapping.')]],
      '#status_headings' => [
        'warning' => $this->t('Warning message'),
      ],
    ];
    $form['names']['prefixes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prefixes'),
      '#description' => $this->t('Enter one value per line, in the format search|replace.'),
      '#default_value' => $this->keyValuesString($config->get('names.prefixes')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['suffixes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Suffixes'),
      '#description' => $this->t('Enter one value per line, in the format search|replace.'),
      '#default_value' => $this->keyValuesString($config->get('names.suffixes')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['abbreviations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Abbreviations'),
      '#description' => $this->t('Enter one value per line, in the format search|replace.'),
      '#default_value' => $this->keyValuesString($config->get('names.abbreviations')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['custom_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom names'),
      '#description' => $this->t('Enter one value per line, in the format search|replace.'),
      '#default_value' => $this->keyValuesString($config->get('names.custom_names')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['custom_titles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom titles'),
      '#description' => $this->t('Enter one value per line, in the format search|replace.'),
      '#default_value' => $this->keyValuesString($config->get('names.custom_titles')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['acronyms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Acronyms'),
      '#description' => $this->t('Enter one value per line.'),
      '#default_value' => $this->listString($config->get('names.acronyms')),
      '#element_validate' => ['::validateList'],
    ];
    $form['names']['minor_words'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Minor words'),
      '#description' => $this->t('Enter one value per line.'),
      '#default_value' => $this->listString($config->get('names.minor_words')),
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
      ->set('names', $form_state->getValue('names'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
