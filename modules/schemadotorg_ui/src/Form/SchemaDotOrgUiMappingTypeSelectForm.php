<?php

namespace Drupal\schemadotorg_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Schema.org UI type select form.
 */
class SchemaDotOrgUiMappingTypeSelectForm extends FormBase {

  /**
   * The module handler.
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
   * The Schema.org schema type builder service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_ui_mapping_type_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    // Description top.
    if ($this->moduleHandler->moduleExists('schemadotorg_report')
      && $this->currentUser()->hasPermission('access site reports')) {
      $t_args = [
        ':types_href' => Url::fromRoute('schemadotorg_report.types')->toString(),
        ':properties_href' => Url::fromRoute('schemadotorg_report.properties')->toString(),
        ':things_href' => Url::fromRoute('schemadotorg_report.types.things')->toString(),
      ];
      $description_top = $this->t('The schemas are a set of <a href=":types_href">types</a>, each associated with a set of <a href=":properties_href">properties</a>.', $t_args);
      $description_top .= ' ' . $this->t('The types are arranged in a <a href=":things_href">hierarchy</a>.', $t_args);
    }
    else {
      $description_top = $this->t("The schemas are a set of 'types', each associated with a set of properties.");
      $description_top .= ' ' . $this->t('The types are arranged in a hierarchy.');
    }
    $form['description'] = ['#markup' => $description_top];

    // Find.
    $t_args = ['@label' => $this->t('type')];
    $form['find'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['find']['type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Find a @label', $t_args),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Find a Schema.org @label', $t_args),
      '#size' => 30,
      '#required' => TRUE,
      '#autocomplete_route_name' => 'schemadotorg.autocomplete',
      '#autocomplete_route_parameters' => ['table' => 'Thing'],
      '#attributes' => ['class' => ['schemadotorg-autocomplete']],
      '#attached' => ['library' => ['schemadotorg/schemadotorg.autocomplete']],
    ];
    $form['find']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Find'),
      '#validate' => [static::class . '::validateTypeForm'],
      '#submit' => [static::class . '::submitTypeForm'],
    ];

    // Description bottom.
    // Display recommended Schema.org types.
    $entity_type_id = $entity_type_id ?? 'node';
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $recommended_types = $mapping_type_storage->getRecommendedSchemaTypes($entity_type_id);
    $items = [];
    foreach ($recommended_types as $group_name => $group) {
      $item = [];
      $item['group'] = [
        '#markup' => $group['label'],
        '#prefix' => '<strong>',
        '#suffix' => ':</strong> ',
      ];
      foreach ($group['types'] as $type) {
        $item[$type] = $this->buildSchemaTypeItem($entity_type_id, $type)
          + ['#prefix' => (count($item) > 1) ? ', ' : ''];
      }
      $items[$group_name] = $item;
    }
    $form['description_bottom'] = [
      'intro' => ['#markup' => '<p>' . $this->t('Or you can jump directly to a commonly used type:') . '</p>'],
      'items' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];

    // Types tree.
    $tree = $this->schemaTypeManager->getTypeTree('Thing');
    $base_path = Url::fromRoute('<current>', [], ['query' => ['type' => '']])->setAbsolute()->toString();
    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Full list of Schema.org types'),
      'tree' => $this->schemaTypeBuilder->buildTypeTree($tree, ['base_path' => $base_path]),
    ];

    // Drush commands.
    $commands = [];
    foreach ($recommended_types as $group_name => $group) {
      $arguments = [];
      foreach ($group['types'] as $type) {
        $arguments[] = "$entity_type_id:$type";
      }
      $commands[] = '# ' . $group['label'];
      $commands[] = 'drush create:type ' . implode(' ', $arguments);
      $commands[] = '';
    }
    $form['drush'] = [
      '#type' => 'details',
      '#title' => $this->t('Drush commands'),
      '#description' => $this->t('Use the below drush commands to create commonly used types.'),
      'commands' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => implode(PHP_EOL, $commands),
      ],
    ];
    return $form;
  }

  /**
   * Validate the Schema.org type.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateTypeForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('type');
    /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager */
    $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
    if (!$schema_type_manager->isThing($type)) {
      $t_args = ['%type' => $type];
      $form_state->setErrorByName('type', t("The Schema.org type %type is not valid.", $t_args));
    }
  }

  /**
   * Submit the Schema.org type.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitTypeForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('type');
    $form_state->setRedirect('<current>', [], ['query' => ['type' => $type]]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing because the submit callback is already handled.
    // @see \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiTypeSelectForm::submitTypeForm
  }

  /**
   * Build Schema.org type item to be displayed in comma or hierarchical lists.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   A renderable array containing the Schema.org type item.
   */
  protected function buildSchemaTypeItem($entity_type_id, $type) {
    $schema_mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    if ($schema_mapping_storage->isSchemaTypeMapped($entity_type_id, $type)) {
      return ['#markup' => $type];
    }
    else {
      return [
        '#type' => 'link',
        '#title' => $type,
        '#url' => Url::fromRoute('<current>', [], ['query' => ['type' => $type]]),
      ];
    }
  }

}
