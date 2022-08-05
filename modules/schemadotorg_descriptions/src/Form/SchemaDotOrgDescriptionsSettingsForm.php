<?php

namespace Drupal\schemadotorg_descriptions\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org Descriptions settings.
 */
class SchemaDotOrgDescriptionsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_descriptions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_descriptions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_descriptions.settings');

    $form['schemadotorg_descriptions'] = [
      '#type' => 'details',
      '#title' => $this->t('Description settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_descriptions']['custom_descriptions'] = [
      '#title' => $this->t('Custom Schema.org type and property descriptions'),
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'type|description or property|description',
      '#description' => $this->t('Enter custom Schema.org type and property descriptions. Leave the description blank to remove the default description provided by Schema.org.'),
      '#description_link' => 'types',
      '#default_value' => $config->get('custom_descriptions'),
    ];
    $form['schemadotorg_descriptions']['trim_descriptions'] = [
      '#title' => $this->t('Trim long Schema.org type and property descriptions'),
      '#type' => 'checkbox',
      '#description' => $this->t("If checked, long Schema.org type and property descriptions will be truncated to the first paragraphs and a 'learn more' link will be appended to the description."),
      '#default_value' => $config->get('trim_descriptions'),
      '#return_value' => TRUE,
    ];
    $form['schemadotorg_descriptions']['help_descriptions'] = [
      '#title' => $this->t('Set explanation or submission guidelines to the Schema.org type descriptions'),
      '#type' => 'checkbox',
      '#description' => $this->t("If checked, Schema.org type descriptions will also be displayed as the explanation/submission guidelines. Explanation/submission guidelines are only applicable to content types."),
      '#default_value' => $config->get('help_descriptions'),
      '#return_value' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear cache bins to make sure descriptions are updated.
    $cache_backends = Cache::getBins();
    $service_ids = ['data', 'discovery', 'dynamic_page_cache'];
    foreach ($service_ids as $service_id) {
      if (isset($cache_backends[$service_id])) {
        $cache_backends[$service_id]->deleteAll();
      }
    }

    $config = $this->config('schemadotorg_descriptions.settings');
    $values = $form_state->getValue('schemadotorg_descriptions');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
