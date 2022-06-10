<?php

namespace Drupal\schemadotorg_jsonld\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org JSON-LD settings for this site.
 */
class SchemaDotOrgJsonLdSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_jsonld_settings_form';
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

    $form['identifiers'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'field_name|identifier',
      '#title' => $this->t('Schema.org identifiers'),
      '#description' => $this->t('Enter the field names to be used to <a href=":href">Schema.org identifier</a>.', [':href' => 'https://schema.org/docs/datamodel.html#identifierBg']),
      '#default_value' => $config->get('identifiers'),
    ];
    $form['property_order'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#settings_format' => 'propertyName',
      '#title' => $this->t('Schema.org property order'),
      '#description' => $this->t('Enter the default Schema.org property order.'),
      '#default_value' => $config->get('property_order'),
    ];
    $form['property_image_styles'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'propertyName|image_style',
      '#title' => $this->t('Schema.org property image styles'),
      '#description' => $this->t('Enter the Schema.org property and the desired image style.'),
      '#default_value' => $config->get('property_image_styles'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg_jsonld.settings')
      ->set('identifiers', $form_state->getValue('identifiers'))
      ->set('property_order', $form_state->getValue('property_order'))
      ->set('property_image_styles', $form_state->getValue('property_image_styles'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
