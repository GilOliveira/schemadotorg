<?php

namespace Drupal\schemadotorg_mapping_set\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org mapping set settings.
 */
class SchemaDotOrgMappingSetSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_mapping_set_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_mapping_set.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_mapping_set.settings');
    $form['sets'] = [
      '#type' => 'schemadotorg_settings',
      '#rows' => 12,
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#settings_format' => 'set_name|set_label|entity_type_id:SchemaType01,entity_type_id:SchemaType02',
      '#array_name' => 'types',
      '#title' => $this->t('Mapping sets'),
      '#description' => $this->t('Enter Schema.org mapping sets by name, label, and entity type to Schema.org type.'),
      '#default_value' => $config->get('sets'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg_mapping_set.settings')
      ->set('required', $form_state->getValue('required'))
      ->set('sets', $form_state->getValue('sets'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
