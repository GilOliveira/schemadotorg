<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org report settings.
 */
class SchemaDotOrgReportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_report_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_report.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('schemadotorg_report.settings');
    $form['schemadotorg_report'] = [
      '#type' => 'details',
      '#title' => $this->t('Reference settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_report']['about'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::LINKS,
      '#title' => $this->t('Schema.org about links'),
      '#description' => $this->t('Enter links to general information about Schema.org.'),
      '#default_value' => $config->get('about'),
    ];
    $form['schemadotorg_report']['types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::LINKS_GROUPED,
      '#title' => $this->t('Schema.org type specific links'),
      '#description' => $this->t('Enter links to specific information about Schema.org types.'),
      '#default_value' => $config->get('types'),
    ];
    $form['schemadotorg_report']['issues'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::LINKS_GROUPED,
      '#title' => $this->t('Schema.org type issue/discussion links'),
      '#description' => $this->t('Enter links to specific issues/discussions about Schema.org types.'),
      '#default_value' => $config->get('issues'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('schemadotorg_report.settings');
    $values = $form_state->getValue('schemadotorg_report');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }

    parent::submitForm($form, $form_state);
  }

}
