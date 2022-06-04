<?php

namespace Drupal\schemadotorg_descriptions\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org Descriptions settings for this site.
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
    $form['custom_descriptions'] = [
      '#title' => $this->t('Custom Schema.org type and property descriptions'),
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'type|description or property|description',
      '#description' => $this->t('Enter custom Schema.org type and property descriptions. Leave the description blank to remove the default description provided by Schema.org.'),
      '#default_value' => $config->get('custom_descriptions'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cache_backends = Cache::getBins();
    $service_ids = ['data', 'discovery', 'dynamic_page_cache'];
    foreach ($service_ids as $service_id) {
      if (isset($cache_backends[$service_id])) {
        $cache_backends[$service_id]->deleteAll();
      }
    }

    $this->config('schemadotorg_descriptions.settings')
      ->set('custom_descriptions', $form_state->getValue('custom_descriptions'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
