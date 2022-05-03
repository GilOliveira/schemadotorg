<?php

namespace Drupal\schemadotorg_jsonapi_extras\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Form\SchemaDotOrgFormTrait;

/**
 * Configure Schema.org JSON:API settings for this site.
 */
class SchemaDotOrgJsonApiExtrasSettingsForm extends ConfigFormBase {
  use SchemaDotOrgFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_jsonapi_extras_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_jsonapi_extras.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_jsonapi_extras.settings');
    $form['default_enabled_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default enabled fields'),
      '#description' => $this->t('Enter fields that should default to enabled when they are added to a Schema.org JSON:API resource.')
      . '<br/><br/>'
      . $this->t('Enter one field per line.'),
      '#default_value' => $this->listString($config->get('default_enabled_fields')),
      '#element_validate' => ['::validateList'],
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
    $this->config('schemadotorg_jsonapi_extras.settings')
      ->set('default_enabled_fields', $form_state->getValue('default_enabled_fields'))
      ->set('disable_requirements', $form_state->getValue('disable_requirements'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
