<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org types settings for this site.
 */
class SchemaDotOrgSettingsTypesForm extends ConfigFormBase {
  use SchemaDotOrgFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_types_settings';
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
    $form['schema_types']['default_properties'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type properties'),
      '#description' => $this->t('Enter one value per line, in the format format SchemaType|propertyName01,propertyName02,propertyName02.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->nestedListString($config->get('schema_types.default_properties')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['schema_types']['default_field_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org type field types'),
      '#description' => $this->t('Enter one value per line, in the format format SchemaType|field_type_01,field_type_02,field_type_03.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->nestedListString($config->get('schema_types.default_field_types')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['schema_types']['default_subtypes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org subtypes'),
      '#description' => $this->t('Enter one Schema.org type per line.'),
      '#default_value' => $this->listString($config->get('schema_types.default_subtypes')),
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
      ->save();
    parent::submitForm($form, $form_state);
  }

}
