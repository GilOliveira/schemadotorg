<?php

namespace Drupal\schemadotorg_demo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org Demo settings.
 */
class SchemaDotOrgDemoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_demo_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_demo.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_demo.settings');
    $form['demos'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#settings_format' => 'demo_name|demo_label|entity_type_id:SchemaType01,entity_type_id:SchemaType02',
      '#array_name' => 'types',
      '#title' => $this->t('Demos'),
      '#description' => $this->t('Enter Drupal entity type and Schema.org types to demo.'),
      '#default_value' => $config->get('demos'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg_demo.settings')
      ->set('required', $form_state->getValue('required'))
      ->set('demos', $form_state->getValue('demos'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
