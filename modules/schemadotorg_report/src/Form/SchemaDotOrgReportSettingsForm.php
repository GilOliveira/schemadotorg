<?php

namespace Drupal\schemadotorg_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org report settings for this site.
 */
class SchemaDotOrgReportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_report_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_report.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_report.settings');
    $form['about'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::LINKS,
      '#title' => $this->t('Schema.org about links'),
      '#description' => $this->t('Enter links to general information about Schema.org.'),
      '#default_value' => $config->get('about'),
    ];
    $form['types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::LINKS_GROUPED,
      '#title' => $this->t('Schema.org type specific links'),
      '#description' => $this->t('Enter links to specific information about Schema.org types.'),
      '#default_value' => $config->get('types'),
    ];
    $form['issues'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::LINKS_GROUPED,
      '#title' => $this->t('Schema.org type issues/discussions links'),
      '#description' => $this->t('Enter links to specific issues/discussions about Schema.org types.'),
      '#default_value' => $config->get('issues'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg_report.settings')
      ->set('about', $form_state->getValue('about'))
      ->set('types', $form_state->getValue('types'))
      ->set('issues', $form_state->getValue('issues'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
