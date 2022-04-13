<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org properties settings for this site.
 */
class SchemaDotOrgSettingsPropertiesForm extends ConfigFormBase {
  use SchemaDotOrgFormTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_properties_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg.settings');

    $form['schema_properties'] = [
      '#tree' => TRUE,
    ];
    $form['schema_properties']['field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Schema.org property field prefix'),
      '#description' => $this->t('Schema.org property field prefix cannot be updated after mappings have been created.'),
      '#default_value' => $config->get('field_prefix'),
      '#parents' => ['field_prefix'],
    ];
    if ($this->entityTypeManager->getStorage('schemadotorg_mapping')->loadMultiple()) {
      $form['schema_properties']['field_prefix']['#disabled'] = TRUE;
      $form['schema_properties']['field_prefix']['#value'] = $config->get('field_prefix');
    }
    $form['schema_properties']['default_field_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default Schema.org property field types'),
      '#description' => $this->t('Enter one value per line, in the format <code>propertyName|field_type_01,field_type_02,field_type_03</code>.'),
      '#attributes' => ['wrap' => 'off'],
      '#default_value' => $this->nestedListString($config->get('schema_properties.default_field_types')),
      '#element_validate' => ['::validateNestedList'],
    ];
    $form['schema_properties']['default_unlimited_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default unlimited Schema.org properties'),
      '#description' => $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($config->get('schema_properties.default_unlimited_fields')),
      '#element_validate' => ['::validateList'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg.settings')
      ->set('field_prefix', $form_state->getValue('field_prefix'))
      ->set('schema_properties', $form_state->getValue('schema_properties'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
