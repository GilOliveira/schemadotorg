<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org settings for this site.
 */
class SchemaDotOrgSettingsNamesForm extends ConfigFormBase {
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
    return 'schemadotorg_names_settings';
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
      '#type' => 'textarea',
      '#title' => $this->t('Prefixes'),
      '#description' => $this->t('Enter replacement prefixes used when Schema.org types and names are converted to Drupal entity and field machine names.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>search|replace</code>.'),
      '#default_value' => $this->keyValuesString($config->get('names.prefixes')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['suffixes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Suffixes'),
      '#description' => $this->t('Enter replacement suffixes used when Schema.org types and names are converted to Drupal entity and field machine names.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>search|replace</code>.'),
      '#default_value' => $this->keyValuesString($config->get('names.suffixes')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['abbreviations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Abbreviations'),
      '#description' => $this->t('Enter replacement abbreviation used when Schema.org types and names are converted to Drupal entity and field machine names.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>search|replace</code>.'),
      '#default_value' => $this->keyValuesString($config->get('names.abbreviations')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['custom_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom names'),
      '#description' => $this->t('Enter custom names used when Schema.org types and names are converted to Drupal entity and field machine names.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>search|replace</code>.'),
      '#default_value' => $this->keyValuesString($config->get('names.custom_names')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['custom_titles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom titles'),
      '#description' => $this->t('Enter titles used when Schema.org types and names are converted to Drupal entity and field machine names.')
      . '<br/><br/>'
      . $this->t('Enter one value per line, in the format <code>search|replace</code>.'),
      '#default_value' => $this->keyValuesString($config->get('names.custom_titles')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['names']['acronyms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Acronyms'),
      '#description' => $this->t('Enter acronyms used when creating labels.')
      . '<br/><br/>'
      . $this->t('Enter one value per line.'),
      '#default_value' => $this->listString($config->get('names.acronyms')),
      '#element_validate' => ['::validateList'],
    ];
    $form['names']['minor_words'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Minor words'),
      '#description' => $this->t('Enter minor word used when creating capitalized labels.')
      . '<br/><br/>'
      . $this->t('Enter one value per line.'),
      '#default_value' => $this->listString($config->get('names.minor_words')),
      '#element_validate' => ['::validateList'],
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
