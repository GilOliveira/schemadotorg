<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org settings for this site.
 */
class SchemaDotOrgSettingsNamesForm extends ConfigFormBase {

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
    return 'schemadotorg_names_settings_form';
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

    // Display warning about updating names.
    $message = $this->t('Adjusting prefixes, suffixes, and abbreviations can impact existing Schema.org mappings because the expected Drupal field names can change.');
    $this->messenger()->addWarning($message);

    $form['#tree'] = TRUE;
    $form['names'] = [
      '#type' => 'container',
    ];
    $form['names']['prefixes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Prefixes'),
      '#description' => $this->t('Enter replacement prefixes used when Schema.org types and names are converted to Drupal entity and field machine names.'),
      '#default_value' => $config->get('names.prefixes'),
    ];
    $form['names']['suffixes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Suffixes'),
      '#description' => $this->t('Enter replacement suffixes used when Schema.org types and names are converted to Drupal entity and field machine names.'),
      '#default_value' => $config->get('names.suffixes'),
    ];
    $form['names']['abbreviations'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Abbreviations'),
      '#description' => $this->t('Enter replacement abbreviation used when Schema.org types and names are converted to Drupal entity and field machine names.'),
      '#default_value' => $config->get('names.abbreviations'),
    ];
    $form['names']['custom_names'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Custom names'),
      '#description' => $this->t('Enter custom names used when Schema.org types and names are converted to Drupal entity and field machine names.'),
      '#default_value' => $config->get('names.custom_names'),
    ];
    $form['names']['custom_words'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Custom titles'),
      '#description' => $this->t('Enter titles used when Schema.org types and names are converted to Drupal entity and field machine names.'),
      '#default_value' => $config->get('names.custom_words'),
    ];
    $form['names']['custom_labels'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Custom labels'),
      '#description' => $this->t('Enter replacement labels used when Schema.org types and names are displayed as a Drupal entity and field machine label.')
      . ' '
      . $this->t('Schema.org type and property names are case-sensitive and must be an exact match.'),
      '#default_value' => $config->get('names.custom_labels'),
    ];
    $form['names']['acronyms'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Acronyms'),
      '#description' => $this->t('Enter acronyms used when creating labels.'),
      '#default_value' => $config->get('names.acronyms'),
    ];
    $form['names']['minor_words'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Minor words'),
      '#description' => $this->t('Enter minor word used when creating capitalized labels.'),
      '#default_value' => $config->get('names.minor_words'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('schemadotorg.settings')
      ->set('names', $form_state->getValue('names'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
