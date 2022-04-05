<?php

namespace Drupal\schemadotorg_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Form\SchemaDotOrgFormTrait;
use Drupal\schemadotorg_report\Controller\SchemaDotOrgReportReferencesController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org report settings for this site.
 */
class SchemaDotOrgReportSettingsForm extends ConfigFormBase {
  use SchemaDotOrgFormTrait;

  /**
   * The Schema.org report references service.
   *
   * @var \Drupal\schemadotorg_report\SchemaDotOrgReportReferencesInterface
   */
  protected $schemaReferences;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_report_settings';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->schemaReferences = $container->get('schemadotorg_report.references');
    return $instance;
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
      '#description' => $this->t('Enter one value per line.'),
      '#default_value' => $this->listString($config->get('about')),
      '#element_validate' => ['::validateList'],
    ];
    $form['types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Schema.org type specific links'),
      '#description' => $this->t('Enter one value per line. Enter Schema.org type followed by individual URLs.'),
      '#default_value' => $this->groupedUrlsString($config->get('types')),
      '#element_validate' => ['::validateGroupedUrls'],
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
    $this->schemaReferences->resetReferences();
    $this->schemaReferences->getReferences();
    parent::submitForm($form, $form_state);
  }

}
