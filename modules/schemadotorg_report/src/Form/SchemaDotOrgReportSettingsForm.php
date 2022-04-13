<?php

namespace Drupal\schemadotorg_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Form\SchemaDotOrgFormTrait;

/**
 * Configure Schema.org report settings for this site.
 */
class SchemaDotOrgReportSettingsForm extends ConfigFormBase {
  use SchemaDotOrgFormTrait;

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
      '#type' => 'textarea',
      '#title' => $this->t('Schema.org about links'),
      '#description' => $this->t('Enter one link per line, in the format <code>uri|title</code>.'),
      '#default_value' => $this->linksString($config->get('about')),
      '#element_validate' => ['::validateLinks'],
    ];
    $form['types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Schema.org type specific links'),
      '#description' => $this->t('Enter one item per line. Enter Schema.org type followed by individual links, in the format <code>uri|title</code>.'),
      '#default_value' => $this->groupedLinksString($config->get('types')),
      '#element_validate' => ['::validateGroupedLinks'],
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
      ->save();

    parent::submitForm($form, $form_state);
  }

}
