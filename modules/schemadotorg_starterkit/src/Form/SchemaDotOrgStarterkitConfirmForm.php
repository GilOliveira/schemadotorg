<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class SchemaDotOrgStarterkitConfirmForm extends ConfirmFormBase {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * The Schema.org mapping manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $schemaMappingManager;

  /**
   * The Schema.org starterkitmanager service.
   *
   * @var \Drupal\schemadotorg_starterkit\SchemaDotOrgStarterkitManagerInterface
   */
  protected $schemaStarterkitManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    $instance->schemaMappingManager = $container->get('schemadotorg.mapping_manager');
    $instance->schemaStarterkitManager = $container->get('schemadotorg_starterkit.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_starterkit_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    $t_args = [
      '@action' => $this->getAction(),
      '%name' => $this->getLabel(),
    ];
    return $this->t("Are you sure you want to @action the %name starterkit?", $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    $t_args = [
      '@action' => $this->getAction(),
      '%name' => $this->getLabel(),
    ];
    return $this->t('Please confirm that you want @action the %name starterkit.', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('schemadotorg_starterkit.overview');
  }

  /**
   * The starterkitname.
   *
   * @var string
   */
  protected $name;

  /**
   * The starterkitoperation to be performed.
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

    $settings = $this->schemaStarterkitManager->getStarterkitSettings($this->name);

    $form['description'] = [
      'description' => $form['description'] + ['#prefix' => '<p>', '#suffix' => '</p>'],
      'types' => $this->buildSchemaTypes($settings['types'], $operation),
    ];

    switch ($this->operation) {
      case 'install':
        // Add note after the actions element which has a weight of 100.
        $form['note'] = [
          '#weight' => 101,
          '#markup' => $this->t('Please note that the installation and setting up of multiple entity types and fields may take a minute or two to complete.'),
          '#prefix' => '<div><em>',
          '#suffix' => '</em></div>',
        ];
        break;
    }

    if ($form_state->isMethodType('get')
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

    $operations = [];
    $operations['install'] = $this->t('installed');
    $operations['generate'] = $this->t('generated');
    $operations['kill'] = $this->t('killed');

    try {
      $this->schemaStarterkitManager->$operation($name);

      // Display a custom message.
      $t_args = [
        '@action' => $operations[$this->operation],
        '%name' => $this->getLabel(),
      ];
      $this->messenger()->addStatus($this->t('The %name starterkit has been @action.', $t_args));
    }
    catch (\Exception $exception) {
      // Display a custom message.
      $t_args = [
        '@action' => $operations[$this->operation],
        '%name' => $this->getLabel(),
      ];
      $this->messenger()->addStatus($this->t('The %name starterkit has failed to be @action.', $t_args));
      $this->messenger->addError($exception->getMessage());
    }

    // Redirect to the starterkit manage page.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Get the current starterkit's label.
   *
   * @return string
   *   The current starterkit's label.
   */
  protected function getLabel(): string {
    $starterkit = $this->schemaStarterkitManager->getStarterkit($this->name);
    if (!$starterkit) {
      throw new NotFoundHttpException();
    }
    return $starterkit['name'];
  }

  /**
   * Get the current starterkit's action.
   *
   * @return string
   *   The current starterkit's action.
   */
  protected function getAction(): TranslatableMarkup {
    $is_installed = $this->moduleHandler->moduleExists($this->name);
    $operations = [];
    if (!$is_installed) {
      if ($this->currentUser()->hasPermission('administer modules')) {
        $operations['install'] = $this->t('install');
      }
    }
    elseif ($this->moduleHandler->moduleExists('devel_generate')) {
      $operations['generate'] = $this->t('generate');
      $operations['kill'] = $this->t('kill');
    }
    if (!isset($operations[$this->operation])) {
      throw new NotFoundHttpException();
    }
    return $operations[$this->operation];
  }

  /**
   * Get the current starterkit's name.
   *
   * @return string
   *   the current starterkit's name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Get the current starterkit's operation.
   *
   * @return string
   *   the current starterkit's operation.
   */
  public function getOperation(): string {
    return $this->operation;
  }

  /**
   * Build Schema.org types details.
   *
   * @param array $types
   *   An array of Schema.org types.
   * @param string|null $operation
   *   An operation.
   *
   * @return array
   *   A renderable array containing Schema.org types details.
   */
  protected function buildSchemaTypes(array $types, ?string $operation = NULL): array {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping');

    $build = [];
    foreach ($types as $type => $mapping_defaults) {
      [$entity_type_id, $schema_type] = explode(':', $type);

      // Reload the mapping default without any alterations.
      if ($operation !== 'install') {
        $mapping_defaults = $this->schemaMappingManager->getMappingDefaults($entity_type_id, $mapping_defaults['entity']['id'], $schema_type);
      }

      $details = $this->buildSchemaType($type, $mapping_defaults);
      switch ($operation) {
        case 'install':
          $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
          $details['#title'] .= ' - ' . ($mapping ? $this->t('Exists') : '<em>' . $this->t('Missing') . '</em>');
          $details['#summary_attributes']['class'] = [($mapping) ? 'color-success' : 'color-warning'];
          break;
      }
      $build[$type] = $details;
    }
    return $build;
  }

  /**
   * Build a Schema.org type's details.
   *
   * @param string $type
   *   A Schema.org type.
   * @param array $mapping_defaults
   *   A Schema.org type's mapping defaults.
   *
   * @see \Drupal\schemadotorg_mapping_set\Controller\SchemadotorgMappingSetController::buildSchemaType
   */
  protected function buildSchemaType(string $type, array $mapping_defaults): array {
    [$entity_type_id, $schema_type] = explode(':', $type);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping');

    $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
    if ($mapping) {
      if ($mapping->getTargetEntityBundleEntity()) {
        $entity_type = $mapping->getTargetEntityBundleEntity()
          ->toLink($type, 'edit-form')->toRenderable();
      }
      elseif ($mapping->getTargetEntityTypeId() === 'user') {
        $entity_type = Link::createFromRoute($type, 'entity.user.admin_form')->toRenderable();
      }
      else {
        $entity_type = [
          '#markup' => $entity_type_id . ':' . $mapping_defaults['entity']['id'],
        ];
      }
    }
    else {
      $entity_type = [
        '#markup' => $entity_type_id . ':' . $mapping_defaults['entity']['id'],
      ];
    }

    $t_args = [
      '@label' => $mapping_defaults['entity']['label'],
      '@type' => $type,
    ];
    $details = [
      '#type' => 'details',
      '#title' => $this->t('@label (@type)', $t_args),
    ];

    // Entity.
    $details['schema_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Schema.org type'),
      'link' => $this->schemaTypeBuilder->buildItemsLinks($schema_type, ['attributes' => ['target' => '_blank']]),
    ];
    $details['entity_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity type and bundle'),
      'item' => $entity_type,
    ];
    $details['label'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity label'),
      '#markup' => $mapping_defaults['entity']['label'],
    ];

    $details['entity_description'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity description'),
      '#markup' => $mapping_defaults['entity']['description'],
    ];

    // Properties.
    $rows = [];
    $field_prefix = $this->config('schemadotorg.settings')->get('field_prefix');
    foreach ($mapping_defaults['properties'] as $property_name => $property_definition) {
      if (empty($property_definition['name'])) {
        continue;
      }

      if (empty($property_definition['name'])
        || empty($property_definition['label'])) {
        continue;
      }
      $range_includes = $this->schemaTypeManager->getPropertyRangeIncludes($property_name);

      $row = [];
      $row['label'] = [
        'data' => [
          'name' => [
            '#markup' => $property_definition['label'],
            '#prefix' => '<strong>',
            '#suffix' => '</strong></br>',
          ],
          'description' => [
            '#markup' => $property_definition['description'],
            '#suffix' => '</br>',
          ],
          'range_includes' => $range_includes ? [
            'links' => $this->schemaTypeBuilder->buildItemsLinks($range_includes, ['attributes' => ['target' => '_blank']]),
            '#prefix' => '(',
            '#suffix' => ')',
          ] : [],
        ],
      ];
      $row['property'] = $property_name;
      $row['arrow'] = '→';
      $exists = ($property_definition['name'] !== SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD);
      if ($exists) {
        $row['name'] = $property_definition['name'];
        $row['existing'] = $this->t('Yes');
      }
      else {
        $row['name'] = $field_prefix . $property_definition['machine_name'];
        $row['existing'] = $this->t('No');
      }
      $row['type'] = $property_definition['type'];
      $row['unlimited'] = !empty($property_definition['unlimited']) ? $this->t('Yes') : $this->t('No');
      $row['required'] = !empty($property_definition['required']) ? $this->t('Yes') : $this->t('No');
      $rows[] = [
        'data' => $row,
        'class' => [$exists ? 'color-success' : 'color-warning'],
      ];
    }
    $details['properties'] = [
      '#type' => 'table',
      '#header' => [
        'label' => ['data' => $this->t('Label / Description'), 'width' => '35%'],
        'property' => ['data' => $this->t('Schema.org property'), 'width' => '15%'],
        'arrow' => ['data' => '', 'width' => '1%'],
        'name' => ['data' => $this->t('Field name'), 'width' => '15%'],
        'existing' => ['data' => $this->t('Existing field'), 'width' => '10%'],
        'type' => ['data' => $this->t('Field type'), 'width' => '15%'],
        'unlimited' => ['data' => $this->t('Unlimited values'), 'width' => '5%'],
        'required' => ['data' => $this->t('Required field'), 'width' => '5%'],
      ],
      '#rows' => $rows,
    ];
    $details['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';
    return $details;
  }

}
