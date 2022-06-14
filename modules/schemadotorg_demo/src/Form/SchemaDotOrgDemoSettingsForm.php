<?php

namespace Drupal\schemadotorg_demo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org Demo settings for this site.
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
    $form['required'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#settings_format' => 'entity_type:SchemaType',
      '#title' => $this->t('Required types'),
      '#description' => $this->t('Enter Drupal entity type and Schema.org types that should always be required.'),
      '#default_value' => $config->get('required'),
    ];
    $form['demos'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'name|entity_type01:SchemaType01,name|entity_type02:SchemaType02',
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
