<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Schema.org reports filter form base.
 */
abstract class SchemaDotOrgReportsFilterFormBase extends FormBase {

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
   * Name of Schema.org form.
   *
   * @var string
   */
  protected $name;

  /**
   * Label for Schema.org form.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_reports_' . $this->name . '_form';
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
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter'][$this->name] = [
      '#type' => 'textfield',
      '#title' => $this->t('Find a @label', ['@label' => $this->label]),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Find a @label', ['@label' => $this->label]),
      '#size' => '20',
      '#default_value' => $id,
      '#autocomplete_route_name' => 'schemadotorg.reports.autocomplete',
      '#autocomplete_route_parameters' => ['table' => $this->table],
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
    $id = $form_state->getValue($this->name);
    if ($id && $this->manager->isId($this->table, $id)) {
      $form_state->setRedirect('schemadotorg.reports', ['id' => $id]);
    }
    else {
      $form_state->setRedirect('schemadotorg.reports.' . $this->table, [], ['query' => ['id' => $id]]);
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
