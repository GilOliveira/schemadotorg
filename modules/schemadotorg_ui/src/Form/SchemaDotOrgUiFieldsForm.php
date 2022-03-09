<?php

namespace Drupal\schemadotorg_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Schema.org UI form.
 */
class SchemaDotOrgUiFieldsForm extends FormBase {

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
   * The Schema.org entity type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterfacee
   */
  protected $schemaEntityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_ui_fields_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    $instance->schemaEntityTypeManager = $container->get('schemadotorg.entity_type_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $type = $this->getRequest()->query->get('type');

    if (!$type) {
      return $this->buildFindTypeForm($form);
    }

    $type_definition = $this->schemaTypeManager->getType($type);

    $t_args = [
      '@label' => $type_definition['drupal_label'],
      '@name' => $type_definition['drupal_name'],
    ];
    $form['#title'] = $this->t('Add Schema.org @label (@name)', $t_args);
    $form['label'] = [
      '#type' => 'link',
      '#title' => $type_definition['label'],
      '#url' => $this->schemaTypeBuilder->getItemUrl($type_definition['label']),
      '#prefix' => '<div><strong>',
      '#suffix' => '</strong></div>',
    ];
    $form['comment'] = [
      '#markup' => $this->schemaTypeBuilder->formatComment($type_definition['comment']),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The human-readable name of this content type. This text will be displayed as part of the list on the Add content page. This name must be unique.'),
      '#required' => TRUE,
      '#default_value' => $type_definition['drupal_label'],
    ];
    $form['type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine-readable name'),
      '#description' => $this->t('A unique machine-readable name for this content type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the Add content page.'),
      '#required' => TRUE,
      '#pattern' => '[_0-9a-z]+',
      '#default_value' => $type_definition['drupal_name'],
    ];

    $form['properties'] = $this->buildTypeProperties($type);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  protected function buildFindTypeForm(array &$form) {
    // Description top.
    $t_args = [
      ':type_href' => Url::fromRoute('schemadotorg_reports.types')->toString(),
      ':properties_href' => Url::fromRoute('schemadotorg_reports.properties')->toString(),
      ':things_href' => Url::fromRoute('schemadotorg_reports.types.things')->toString(),
    ];
    $description_top = $this->t('The schemas are a set of <a href=":types_href">types</a>, each associated with a set of <a href=":properties_href">properties</a>.', $t_args);
    $description_top .= ' ' . $this->t('The types are arranged in a <a href=":things_href">hierarchy</a>.', $t_args);
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
      '#autocomplete_route_name' => 'schemadotorg.autocomplete',
      '#autocomplete_route_parameters' => ['table' => 'types'],
      '#attributes' => ['class' => ['schemadotorg-autocomplete']],
      '#attached' => ['library' => ['schemadotorg/schemadotorg.autocomplete']],
    ];
    $form['find']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Find'),
    ];

    // Description bottom.
    $description_bottom = '<p>' . $this->t('Or you can jump directly to a commonly used type:') . '</p>';
    $description_bottom .= '<ul>';
    $description_bottom .= '<li>' . $this->t('Creative works: <a title="CreativeWork" href="/CreativeWork">CreativeWork</a>, <a title="Book" href="/Book">Book</a>, <a title="Movie" href="/Movie">Movie</a>, <a title="MusicRecording" href="/MusicRecording">MusicRecording</a>, <a title="Recipe" href="/Recipe">Recipe</a>, <a title="TVSeries" href="/TVSeries">TVSeries</a> ...') . '</li>';
    $description_bottom .= '<li>' . $this->t('Embedded non-text objects: <a title="AudioObject" href="/AudioObject">AudioObject</a>, <a title="ImageObject" href="/ImageObject">ImageObject</a>, <a title="VideoObject" href="/VideoObject">VideoObject</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Event" href="/Event">Event</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('Health and medical types: <a href="/MedicalCondition">MedicalCondition</a>, <a href="/Drug">Drug</a>, <a href="/MedicalGuideline">MedicalGuideline</a>, <a href="/MedicalWebPage">MedicalWebPage</a>, <a href="/MedicalScholarlyArticle">MedicalScholarlyArticle</a>.') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Organization" href="/Organization">Organization</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Person" href="/Person">Person</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Place" href="/Place">Place</a>, <a title="LocalBusiness" href="/LocalBusiness">LocalBusiness</a>, <a title="Restaurant" href="/Restaurant">Restaurant</a> ...') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Product" href="/Product">Product</a>, <a title="Offer" href="/Offer">Offer</a>, <a title="AggregateOffer" href="/AggregateOffer">AggregateOffer</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Review" href="/Review">Review</a>, <a title="AggregateRating" href="/AggregateRating">AggregateRating</a>') . '</li>';
    $description_bottom .= '<li>' . $this->t('<a title="Action" href="/Action">Action</a>') . '</li>';
    $description_bottom .= '</ul>';
    $path = Url::fromRoute('<current>', [], ['query' => ['type' => '']])->toString();
    $form['description_bottom'] = ['#markup' => str_replace('href="/', 'href="' . $path, $description_bottom)];

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo Validate the Schema.org type.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $op = (string) $form_state->getValue('op');
    if ($op === (string) $this->t('Find')) {
      $type = $form_state->getValue('type');
      $form_state->setRedirect('<current>', [], ['query' => ['type' => $type]]);
    }
    elseif ($op === (string) $this->t('Save')) {
      $values = $form_state->getValues();
      dsm($values);
      $this->messenger()->addStatus('Save me!!!');
    }
  }

  /**
   * Build Schema.org type properties table.
   *
   * @return array
   *   A renderable array containing a Schema.org type properties table.
   */
  protected function buildTypeProperties($type) {
    $fields = ['label', 'comment', 'range_includes', 'drupal_label', 'drupal_name'];
    $properties = $this->schemaTypeManager->getTypeProperties($type, $fields);

    // Header.
    $header = [
      'status' => [
        'data' => '',
        'width' => '1%',
      ],
      'label' => [
        'data' => $this->t('Property'),
        'width' => '60%',
      ],
      'definition' => [
        'data' => [
          '#markup' => $this->t('Field label')
            . ' /  <br/>'
            . $this->t('Machine readable-name'),
        ],
        'width' => '20%',
      ],
      'type' => [
        'data' => $this->t('Field type'),
        'width' => '20%',
      ],
      'required' => [
        'data' => $this->t('Required'),
        'width' => '5%',
      ],
      'unlimited' => [
        'data' => $this->t('Unlimited'),
        'width' => '5%',
      ],
    ];

    // Rows.
    $rows = [];
    foreach ($properties as $property => $property_definition) {
      $t_args = ['@property' => $property_definition['label']];
      $row = [];
      $row['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add @property', $t_args),
        '#title_display' => 'invisible',
        '#return_value' => TRUE,
      ];
      $row['label'] = [
        'label' => [
          '#markup' => $property_definition['label'],
          '#prefix' => '<strong>',
          '#suffix' => '</strong><br/>',
        ],
        'comment' => [
          '#markup' => $this->schemaTypeBuilder->formatComment($property_definition['comment']),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ],
        'range_includes' => [
          'links' => $this->schemaTypeBuilder->buildItemsLinks($property_definition['range_includes']),
          '#prefix' => '<div>(',
          '#suffix' => ')</div>',
        ],
      ];
      $row['definition'] = [];
      $row['definition']['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label for @property', $t_args),
        '#title_display' => 'invisible',
        '#default_value' => $property_definition['drupal_label'],
        '#parents' => ['properties', $property, 'label'],
      ];
      $row['definition']['machine_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Machine name for @property'),
        '#title_display' => 'invisible',
        '#maxlength' => 26,
        '#pattern' => '[_0-9a-z]+',
        '#field_prefix' => 'schema_',
        '#default_value' => $property_definition['drupal_name'],
        '#attributes' => ['style' => 'width: 200px'],
        '#wrapper_attributes' => ['style' => 'white-space: nowrap'],
        '#parents' => ['properties', $property, 'machine_name'],
      ];
      $field_type_options = $this->getFieldTypeOptions($property);
      $row['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Field type for @property'),
        '#title_display' => 'invisible',
        '#empty_option' => $this->t('- Select a field type -'),
        '#options' => $field_type_options,
        '#default_value' => array_key_first($field_type_options),
      ];
      $row['required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Require @property', $t_args),
        '#title_display' => 'invisible',
        '#return_value' => TRUE,
      ];
      $row['unlimited'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unlimited values for @property', $t_args),
        '#title_display' => 'invisible',
        '#return_value' => TRUE,
      ];
      $rows[$property] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
    ] + $rows;
  }

  protected function getFieldTypeOptions($property) {
    $field_type_options = $this->schemaEntityTypeManager->getFieldTypesAsOptions();
    $property_options = $this->schemaEntityTypeManager->getSchemaPropertyFieldTypesAsOptions($property);
    return $property_options + [
      (string) $this->t('Other') => array_diff_key($field_type_options, OptGroup::flattenOptions($property_options)),
    ];
  }

}

