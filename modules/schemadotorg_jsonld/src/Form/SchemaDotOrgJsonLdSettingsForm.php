<?php

namespace Drupal\schemadotorg_jsonld\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Form\SchemaDotOrgFormTrait;

/**
 * Configure Schema.org JSON-LD settings for this site.
 */
class SchemaDotOrgJsonLdSettingsForm extends ConfigFormBase {
  use SchemaDotOrgFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_jsonld_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_jsonld.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_jsonld.settings');
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Status'),
      '#default_value' => $config->get('status'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg_jsonld.settings')
      ->set('status', (boolean) $form_state->getValue('status'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
