<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_translation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org Translate settings.
 */
class SchemaDotOrgTranslationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_translation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_translation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('schemadotorg_translation.settings');
    $form['excluded_schema_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Excluded Schema.org types'),
      '#description' => $this->t('Enter Schema.org types that should never be translated.'),
      '#description_link' => 'types',
      '#default_value' => $config->get('excluded_schema_types'),
    ];
    $form['excluded_schema_properties'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Excluded Schema.org properties'),
      '#settings_format' => 'propertyName or SchemaType--propertyName',
      '#description' => $this->t('Enter Schema.org properties that should never be translated.'),
      '#description_link' => 'properties',
      '#default_value' => $config->get('excluded_schema_properties'),
    ];
    $form['excluded_field_names'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Excluded field names'),
      '#description' => $this->t('Enter field names that should never be translated.'),
      '#default_value' => $config->get('excluded_field_names'),
    ];
    $form['included_field_names'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Included field names'),
      '#description' => $this->t('Enter field names that should always be translated.'),
      '#default_value' => $config->get('included_field_names'),
    ];
    $form['included_field_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Included field types'),
      '#description' => $this->t('Enter field types that should always be translated.'),
      '#default_value' => $config->get('included_field_types'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('schemadotorg_translation.settings')
      ->set('excluded_schema_types', $form_state->getValue('excluded_schema_types'))
      ->set('excluded_schema_properties', $form_state->getValue('excluded_schema_properties'))
      ->set('excluded_field_names', $form_state->getValue('excluded_field_names'))
      ->set('included_field_names', $form_state->getValue('included_field_names'))
      ->set('included_field_type', $form_state->getValue('included_field_type'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
