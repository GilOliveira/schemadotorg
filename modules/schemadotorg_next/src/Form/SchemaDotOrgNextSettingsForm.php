<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_next\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Schema.org Next.js settings.
 */
class SchemaDotOrgNextSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_next_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_next.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('schemadotorg_next.settings');

    $form['schemadotorg_next'] = [
      '#type' => 'details',
      '#title' => $this->t('Next.js settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_next']['preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enhance the default Next.js preview'),
      '#description' => $this->t('If checked, the default Next.js preview will be wrapped in details widget and the original Drupal content will be displayed.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('preview'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('schemadotorg_next.settings');
    $values = $form_state->getValue('schemadotorg_next');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
