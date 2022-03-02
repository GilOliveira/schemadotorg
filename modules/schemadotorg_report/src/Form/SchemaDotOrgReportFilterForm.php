<?php

namespace Drupal\schemadotorg_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Schema.org reports filter form.
 */
class SchemaDotOrgReportFilterForm extends FormBase {

  /**
   * The Schema.org manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgManagerInterface
   */
  protected $manager;

  /**
   * Schema.org table.
   *
   * @var string
   */
  protected $table;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_reports_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->manager = $container->get('schemadotorg.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table = NULL, $id = NULL) {
    $this->table = $table;

    $t_args = [
      '@label' => ($table === 'types') ? $this->t('type') : $this->t('properties'),
    ];
    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Find a @label', $t_args),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Find a @label', $t_args),
      '#size' => '20',
      '#default_value' => $id,
      '#autocomplete_route_name' => 'schemadotorg_reports.autocomplete',
      '#autocomplete_route_parameters' => ['table' => $table],
      '#attributes' => ['class' => ['schemadotorg-autocomplete']],
      '#attached' => ['library' => ['schemadotorg/schemadotorg.autocomplete']],
    ];
    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($id)) {
      $form['filter']['reset'] = [
        '#type' => 'submit',
        '#submit' => ['::resetForm'],
        '#value' => $this->t('Reset'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('id');
    if ($id && $this->manager->isId($this->table, $id)) {
      $form_state->setRedirect('schemadotorg_reports', ['id' => $id]);
    }
    else {
      $form_state->setRedirect('schemadotorg_reports.' . $this->table, [], ['query' => ['id' => $id]]);
    }
  }

  /**
   * Resets the filter selection.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getRouteMatch()->getRouteName(), $this->getRouteMatch()->getRawParameters()->all());
  }

}
