<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_mapping_set\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\schemadotorg_mapping_set\Controller\SchemadotorgMappingSetController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class SchemaDotOrgMappingSetConfirmForm extends ConfirmFormBase {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Schema.org mapping set manager service.
   *
   * @var \Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface
   */
  protected $schemaMappingSetManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->moduleHandler = $container->get('module_handler');
    $instance->schemaMappingSetManager = $container->get('schemadotorg_mapping_set.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_mapping_set_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    $t_args = [
      '@action' => $this->getAction(),
      '%name' => $this->getLabel(),
    ];
    return $this->t("Are you sure you want to @action the %name mapping set?", $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    $t_args = [
      '@action' => $this->getAction(),
      '%name' => $this->getLabel(),
    ];
    return $this->t('Please confirm that you want @action the %name mapping set with the below types.', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('schemadotorg_mapping_set.overview');
  }

  /**
   * The mapping set name.
   *
   * @var string
   */
  protected $name;

  /**
   * The mapping set operation to be performed.
   *
   * @var string
   */
  protected $operation;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $name = NULL, ?string $operation = NULL): array {
    $this->name = $name;
    $this->operation = $operation;

    $form = parent::buildForm($form, $form_state);

    $form['description'] = [
      'description' => $form['description'] + ['#prefix' => '<p>', '#suffix' => '</p>'],
      'type' => [
        '#theme' => 'item_list',
        '#items' => $this->schemaMappingSetManager->getTypes($this->name),
      ],
    ];

    if ($this->operation === 'setup') {
      // During setup replace mapping set types with the mapping set's details.
      /** @var \Drupal\schemadotorg_mapping_set\Controller\SchemadotorgMappingSetController $controller */
      $controller = SchemadotorgMappingSetController::create(\Drupal::getContainer());
      $form['description']['type'] = $controller->details($this->name, FALSE);

      // Add note after the actions element which has a weight of 100.
      $form['note'] = [
        '#weight' => 101,
        '#markup' => $this->t('Please note that setting up multiple entity types and fields may take a minute or two to complete.'),
        '#prefix' => '<div><em>',
        '#suffix' => '</em></div>',
      ];
    }

    if ($form_state->isMethodType('GET')
      && in_array($this->operation, ['generate', 'kill'])) {
      $this->messenger()->addWarning($this->t('All existing content will be deleted.'));
    }

    $form['#attributes']['class'][] = 'js-schemadotorg-submit-once';
    $form['#attached'] = ['library' => ['schemadotorg/schemadotorg.form']];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Execute the operation.
    $operation = $this->operation;
    $name = $this->name;
    $this->schemaMappingSetManager->$operation($name);

    // Display a custom message.
    $operations = [];
    $operations['setup'] = $this->t('setup');
    $operations['generate'] = $this->t('generated');
    $operations['kill'] = $this->t('killed');
    $operations['teardown'] = $this->t('torn down');
    $t_args = [
      '@action' => $operations[$this->operation],
      '%name' => $this->getLabel(),
    ];
    $this->messenger()->addStatus($this->t('The %name mapping set has been @action.', $t_args));

    // Redirect to the mapping set manage page.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Get the current mapping set's label.
   *
   * @return string
   *   The current mapping set's label.
   */
  protected function getLabel(): string {
    $mapping_sets = $this->config('schemadotorg_mapping_set.settings')->get('sets');
    if (!isset($mapping_sets[$this->name])) {
      throw new NotFoundHttpException();
    }
    return $mapping_sets[$this->name]['label'];
  }

  /**
   * Get the current mapping set's action.
   *
   * @return string
   *   The current mapping set's action.
   */
  protected function getAction(): TranslatableMarkup {
    $is_setup = $this->schemaMappingSetManager->isSetup($this->name);
    $operations = [];
    if (!$is_setup) {
      $operations['setup'] = $this->t('setup');
    }
    else {
      if ($this->moduleHandler->moduleExists('devel_generate')) {
        $operations['generate'] = $this->t('generate');
        $operations['kill'] = $this->t('kill');
      }
      $operations['teardown'] = $this->t('teardown');
    }
    if (!isset($operations[$this->operation])) {
      throw new NotFoundHttpException();
    }
    return $operations[$this->operation];
  }

}
