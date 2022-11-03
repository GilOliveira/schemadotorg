<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org JSON:API settings.
 */
class SchemaDotOrgJsonApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_jsonapi_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_jsonapi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('schemadotorg_jsonapi.settings');
    $form['schemadotorg_jsonapi'] = [
      '#type' => 'details',
      '#title' => $this->t('JSON:API settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_jsonapi']['default_base_fields'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#settings_format' => 'field_name or entity_type--field_name or entity_type--bundle--field_name',
      '#title' => $this->t('Default base fields'),
      '#description' => $this->t('Enter base fields that should by enabled when they are added to a Schema.org JSON:API resource.')
      . ' '
      . $this->t('Leave blank to enable all base fields by default.'),
      '#default_value' => $config->get('default_base_fields'),
    ];
    $form['schemadotorg_jsonapi']['resource_type_schemadotorg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use Schema.org types as the JSON:API resource's type and path names."),
      '#description' => $this->t("If checked, the Schema.org mapping's type will be used as the JSON:API resource's type and path name.")
      . ' '
      . $this->t('For example, the JSON:API resource page <code>/jsonapi/node/page</code> becomes <code>/jsonapi/node/web_page</code>.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('resource_type_schemadotorg'),
    ];
    $form['schemadotorg_jsonapi']['resource_base_field_schemadotorg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use Schema.org properties as the JSON:API resource's base field names/aliases."),
      '#description' => $this->t("If checked, the Schema.org mapping's property will be used as the JSON:API resource's base field name/alias.")
      . ' '
      . $this->t('For example, the JSON:API resource node base field <code>title</code> becomes <code>name</code>.'),

      '#return_value' => TRUE,
      '#default_value' => $config->get('resource_base_field_schemadotorg'),
    ];
    $form['schemadotorg_jsonapi']['resource_field_schemadotorg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use Schema.org properties as the JSON:API resource's field names/aliases."),
      '#description' => $this->t("If checked, the Schema.org mapping's property will be used as the JSON:API resource's field name/alias."),
      '#return_value' => TRUE,
      '#default_value' => $config->get('resource_field_schemadotorg'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('schemadotorg_jsonapi.settings');
    $values = $form_state->getValue('schemadotorg_jsonapi');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
