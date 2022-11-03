<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org JSON-LD settings.
 */
class SchemaDotOrgJsonLdSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_jsonld_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_jsonld.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('schemadotorg_jsonld.settings');

    $form['schemadotorg_jsonld'] = [
      '#type' => 'details',
      '#title' => $this->t('JSON-LD settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_jsonld']['identifiers'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'field_name|identifier',
      '#title' => $this->t('Schema.org identifiers'),
      '#description' => $this->t('Enter the field names to be used to <a href=":href">Schema.org identifier</a>.', [':href' => 'https://schema.org/docs/datamodel.html#identifierBg']),
      '#default_value' => $config->get('identifiers'),
    ];
    $form['schemadotorg_jsonld']['property_order'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#settings_format' => 'propertyName',
      '#title' => $this->t('Schema.org property order'),
      '#description' => $this->t('Enter the default Schema.org property order.'),
      '#description_link' => 'properties',
      '#default_value' => $config->get('property_order'),
    ];
    $form['schemadotorg_jsonld']['property_image_styles'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'propertyName|image_style',
      '#title' => $this->t('Schema.org property image styles'),
      '#description' => $this->t('Enter the Schema.org property and the desired image style.'),
      '#description_link' => 'properties',
      '#default_value' => $config->get('property_image_styles'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('schemadotorg_jsonld.settings');
    $values = $form_state->getValue('schemadotorg_jsonld');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
