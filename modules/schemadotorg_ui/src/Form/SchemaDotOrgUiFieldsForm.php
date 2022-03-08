<?php

namespace Drupal\schemadotorg_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
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
   * The Schema.org schema data type manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaDataTypeManager;

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
    $instance->schemaDataTypeManager = $container->get('schemadotorg.schema_type_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $type = $this->getRequest()->query->get('type');

    if (!$type) {
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
      return $form;
    }

    $type_definition = $this->schemaDataTypeManager->getType($type);
    $t_args = [
      '@label' => $type_definition['drupal_label'],
      '@name' => $type_definition['drupal_name'],
    ];
    $form['#title'] = $this->t('Add Schema.org @label (@name)', $t_args);
    $form['label'] = [
      '#type' => 'item',
      '#title' => $this->t('Schema.org type'),
      'link' => [
        '#type' => 'link',
        '#title' => $type_definition['label'],
        '#url' => $this->getItemUrl($type_definition['label']),
      ],
    ];
    $form['drupal_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Drupal label'),
      'value' => ['#plain_text' => $type_definition['drupal_label']],
    ];
    $form['drupal_name'] = [
      '#type' => 'item',
      '#title' => $this->t('Drupal name'),
      'value' => ['#plain_text' => $type_definition['drupal_name']],
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
      $this->messenger->addStatus('Save me!!!');
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
    $properties = $this->schemaDataTypeManager->getTypeProperties($type, $fields);

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
        'data' => $this->t('Field Label / Machine name'),
        'width' => '20%',
      ],
      'type' => [
        'data' => $this->t('Field type'),
        'width' => '20%',
      ],
      // @todo Add required and cardinality.
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
      ];
      $row['label'] = [
        'label' => [
          '#markup' => $property_definition['label'],
          '#prefix' => '<strong>',
          '#suffix' => '</strong><br/>',
        ],
        'comment' => [
          '#markup' => $this->formatComment($property_definition['comment']),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ],
        'range_includes' => [
          'links' => $this->buildItemsLinks($property_definition['range_includes']),
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
        '#field_prefix' => 'schema_',
        '#default_value' => $property_definition['drupal_name'],
        '#attributes' => ['style' => 'width: 200px'],
        '#wrapper_attributes' => ['style' => 'white-space: nowrap'],
        '#parents' => ['properties', $property, 'machine_name'],
      ];
      $row['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Field type for @property'),
        '#title_display' => 'invisible',
        '#empty_option' => $this->t('- Select a field type -'),
        '#options' => $this->getFieldTypeOptions($property_definition),
        '#default_value' => '',
      ];
      $rows[$property] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
    ] + $rows;
  }

  /**
   * Build links to Schema.org items (types or properties).
   *
   * @param string $text
   *   A string of comma delimited items (types or properties).
   *
   * @return array
   *   An array of links to Schema.org items (types or properties).
   */
  protected function buildItemsLinks($text) {
    $ids = $this->schemaDataTypeManager->parseIds($text);

    $links = [];
    foreach ($ids as $id) {
      $prefix = ($links) ? ', ' : '';
      if ($this->schemaDataTypeManager->isItem($id)) {
        $links[] = [
          '#type' => 'link',
          '#title' => $id,
          '#url' => $this->getItemUrl($id),
          '#prefix' => $prefix,
        ];
      }
      else {
        $links[] = ['#plain_text' => $id, '#prefix' => $prefix];
      }
    }
    return $links;
  }

  /**
   * Format Schema.org type or property comment.
   *
   * @param string $comment
   *   A comment.
   *
   * @return string
   *   Formatted Schema.org type or property comment with links to details.
   */
  protected function formatComment($comment) {
    if (strpos($comment, 'href="/') === FALSE) {
      return $comment;
    }
    $dom = Html::load($comment);
    $a_nodes = $dom->getElementsByTagName('a');
    foreach ($a_nodes as $a_node) {
      $href = $a_node->getAttribute('href');
      if (preg_match('#^/([0-9A-Za-z]+)$#', $href, $match)) {
        $url = $this->getItemUrl($match[1]);
        $a_node->setAttribute('href', $url->toString());
      }
    }
    return Html::serialize($dom);
  }

  /**
   * Get Schema.org type or property URL.
   *
   * @param string $id
   *   Type or property ID.
   *
   * @return \Drupal\Core\Url
   *   Schema.org type or property URL.
   */
  protected function getItemUrl($id) {
    return Url::fromRoute('schemadotorg_reports', ['id' => $id]);
  }

  protected function getFieldTypeOptions(array $property_definition) {
    /** @var FieldTypePluginManagerInterface $field_types */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    // Get field types as options.
    $options = [];
    $field_types = $field_type_manager->getUiDefinitions();
    foreach ($field_types as $name => $field_type) {
      if (empty($field_type['no_ui'])) {
        $options[$name] = $field_type['label'];
      }
    }
    asort($options);

    // Get recommend field types as options.
    $range_includes = $this->schemaDataTypeManager->parseIds($property_definition['range_includes']);
    $data_type_mappings = [
      // Data types.
      'Text' => ['text', 'text_long', 'string', 'string_long', 'list_string'],
      'Number' => ['integer', 'float', 'decimal', 'list_integer', 'list_float'],
      'DateTime' => ['datetime'],
      'Date' => ['datetime'],
      'Integer' => ['integer', 'list_integer'],
      'Time' => ['datetime'],
      'Boolean' => ['boolean'],
      'URL' => ['link'],
      // @todo Things.
      // @todo Enumerations.
    ];

    $property_mappings = [
      'telephone' => ['telephone'],
    ];

    $recommended_options = [];

    if (isset($property_mappings[$property_definition['label']])) {
      $recommended_options[$property_definition['drupal_label']] = array_intersect_key($options, array_combine($property_mappings[$property_definition['label']], $property_mappings[$property_definition['label']]));
    }

    foreach ($range_includes as $range_include) {
      if (isset($data_type_mappings[$range_include])) {
        $recommended_options[$range_include] = array_intersect_key($options, array_combine($data_type_mappings[$range_include], $data_type_mappings[$range_include]));
      }
    }
    // Default recommended options to entity reference.
    if (!$recommended_options) {
      $recommended_options['Thing']['entity_reference'] = $options['entity_reference'];
    }

    // Build recommended and other options.
    return $recommended_options + [
      (string) $this->t('Other') => array_diff_key($options, OptGroup::flattenOptions($recommended_options)),
    ];
  }

}
