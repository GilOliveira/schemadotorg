<?php

namespace Drupal\schemadotorg_jsonapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org JSON:API settings.
 */
class SchemaDotOrgJsonApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_jsonapi_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_jsonapi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_jsonapi.settings');
    $form['default_enabled_fields'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Default enabled fields'),
      '#description' => $this->t('Enter fields that should default to enabled when they are added to a Schema.org JSON:API resource.')
      . ' '
      . $this->t('Leave blank to enable all fields by default.'),
      '#default_value' => $config->get('default_enabled_fields'),
    ];
    $form['path_prefixes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'entity_type|prefix',
      '#title' => $this->t('Resource path prefixes'),
      '#description' => $this->t('Enter path prefixes to prepended to a Schema.org JSON:API resource when there is a conflicting resource path.')
      . ' '
      . $this->t('For example, adding Person Schema.org type a node and user would create a conflict, that will be resolved by prepending Person with a path prefix (i.e. ContentPerson or UserPerson).'),
      '#default_value' => $config->get('path_prefixes'),
    ];
    $form['disable_requirements'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Schema.org JSON:API requirements checking'),
      '#description' => $this->t("If unchecked, the recommended Schema.org JSON:API requirements will not be checked via Drupal's status report."),
      '#return_value' => TRUE,
      '#default_value' => $config->get('disable_requirements'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg_jsonapi.settings')
      ->set('default_enabled_fields', $form_state->getValue('default_enabled_fields'))
      ->set('disable_requirements', $form_state->getValue('disable_requirements'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
