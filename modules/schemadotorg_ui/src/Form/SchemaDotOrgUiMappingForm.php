<?php

namespace Drupal\schemadotorg_ui\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schema.org mapping form.
 *
 * @see \Drupal\field_ui\Form\EntityDisplayFormBase
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity
 */
class SchemaDotOrgUiMappingForm extends EntityForm {

  /**
   * Add new field mapping option.
   */
  const ADD_FIELD = '_add_';

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

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
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManagerInterface
   */
  protected $schemaEntityTypeManager;

  /**
   * The Schema.org entity type builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface
   */
  protected $schemaEntityTypeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    $instance->schemaEntityTypeManager = $container->get('schemadotorg.entity_type_manager');
    $instance->schemaEntityTypeBuilder = $container->get('schemadotorg.entity_type_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $entity_storage = $this->getEntityStorage();

    $route_parameters = $route_match->getParameters()->all();

    $target_entity_type_id = $route_parameters['entity_type_id'] ?? NULL;
    $target_bundle = $route_parameters['bundle'] ?? NULL;
    $schema_type = $this->getRequest()->query->get('type');

    // Get the Schema.org mapping using route matching.
    if (!$target_entity_type_id && !$schema_type) {
      return parent::getEntityFromRouteMatch($route_match, $entity_type_id);
    }

    // Default the target entity type to be a node.
    $target_entity_type_id = $target_entity_type_id ?? 'node';

    // Make sure the new Schema.org type is not already mapped.
    if ($entity_storage->isSchemaTypeMapped($target_entity_type_id, $schema_type)) {
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
      $entity = $entity_storage->loadBySchemaType($target_entity_type_id, $schema_type);
      $target_entity = $entity->getTargetEntityBundleEntity();
      $t_args = [
        '%type' => $schema_type,
        ':href' => $target_entity->toUrl()->toString(),
        '@label' => $target_entity->label(),
        '@id' => $target_entity->id(),
      ];
      $this->messenger()->addWarning($this->t('%type is currently mapped to <a href=":href">@label</a> (@id).', $t_args));
      $schema_type = NULL;
    }

    // Set default schema type for the current target entity type and bundle.
    $schema_type = $schema_type
      ?: $this->schemaEntityTypeManager->getDefaultSchemaType($target_entity_type_id, $target_bundle);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    $entity = $entity_storage->load($target_entity_type_id . '.' . $target_bundle)
      ?: $entity_storage->create([
        'targetEntityType' => $target_entity_type_id,
        'bundle' => $target_bundle,
        'type' => $schema_type,
      ]);

    // Make sure the Schema.org mapping entity's Schema.org type is set.
    $entity->setSchemaType($entity->getSchemaType() ?: $schema_type);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Disable inline form errors for CLI (a.k.a Drush).
    // @see \Drupal\schemadotorg\Commands\SchemaDotOrgCommands::createType
    if (PHP_SAPI === 'cli') {
      $form['#disable_inline_form_errors'] = TRUE;
    }

    if ($this->getSchemaType()) {
      return $this->buildFieldTypeForm($form);
    }
    else {
      return $this->buildFindTypeForm($form);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    if (!$this->getSchemaType()) {
      return [];
    }
    return parent::actions($form, $form_state);
  }

  /* ************************************************************************ */
  // Submit and save methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $op = (string) $form_state->getValue('op');
    if ($op === (string) $this->t('Find')) {
      return;
    }

    $mapping_entity = $this->getEntity();

    // Validate the bundle entity before it is created.
    if ($mapping_entity->isNewTargetEntityTypeBundle()) {
      $values = $form_state->getValue('entity');
      $bundle_entity_type_id = $mapping_entity->getTargetEntityTypeBundleId();
      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $bundle_entity_storage */
      $bundle_entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
      $bundle_entity = $bundle_entity_storage->load($values['id']);
      if ($bundle_entity) {
        $target_entity_type_bundle_definition = $this->getEntity()->getTargetEntityTypeBundleDefinition();
        $t_args = [
          '%id' => $bundle_entity->id(),
          '@type' => $target_entity_type_bundle_definition->getSingularLabel(),
        ];
        $message = $this->t('A %id @type already exists. Please enter a different name.', $t_args);
        $element = NestedArray::getValue($form, ['entity', 'id']);
        $form_state->setError($element, $message);
      }
    }

    // Validate the new field names before they are created.
    $entity_type_id = $mapping_entity->getTargetEntityTypeId();
    /** @var \Drupal\field\FieldStorageConfigStorage $field_storage_config_storage */
    $field_storage_config_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $properties = $form_state->getValue('properties');
    foreach ($properties as $property_name => $property_values) {
      if ($property_values['field']['name'] === static::ADD_FIELD) {
        $required_element_names = ['type', 'label', 'machine_name'];
        foreach ($required_element_names as $required_element_name) {
          if (empty($property_values['field']['add'][$required_element_name])) {
            $element = NestedArray::getValue($form, ['properties', $property_name, 'field', 'add', $required_element_name]);
            $form_state->setError($element, $this->t('@name field is required.', ['@name' => $element['#title']]));
          }
        }
        if (!empty($property_values['field']['add']['machine_name'])) {
          $field_name = 'schema_' . $property_values['field']['add']['machine_name'];
          if ($field_storage_config_storage->load($entity_type_id . '.' . $field_name)) {
            $element = NestedArray::getValue($form, ['properties', $property_name, 'field', 'add', 'machine_name']);
            $t_args = ['%name' => $field_name];
            $message = $this->t('A %name field already exists. Please enter a different name or select the existing field.', $t_args);
            $form_state->setError($element, $message);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Handle find Schema.org type form.
    $op = (string) $form_state->getValue('op');
    if ($op === (string) $this->t('Find')) {
      $type = $form_state->getValue('type');
      $form_state->setRedirect('<current>', [], ['query' => ['type' => $type]]);
      return;
    }

    $mapping_entity = $this->getEntity();

    // Redirect to the current page if we are update the Schema.org tab
    // in the field UI.
    if (preg_match('/entity\.[a-z]+\.schemadotorg_mapping/', $this->getRouteMatch()->getRouteName())) {
      $form_state->setRedirect('<current>');
    }

    // Create the bundle entity.
    if ($mapping_entity->isNewTargetEntityTypeBundle()) {
      $bundle_entity_type_id = $mapping_entity->getTargetEntityTypeBundleId();
      $bundle_entity_type_definition = $mapping_entity->getTargetEntityTypeBundleDefinition();

      // Get bundle entity values and map id and label keys.
      $bundle_entity_values = $form_state->getValue('entity');
      $keys = ['id', 'label'];
      foreach ($keys as $key) {
        $key_name = $bundle_entity_type_definition->getKey($key);
        if ($key_name !== $key) {
          $bundle_entity_values[$key_name] = $bundle_entity_values[$key];
          unset($bundle_entity_values[$key]);
        }
      }

      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $bundle_entity_storage */
      $bundle_entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
      $bundle_entity = $bundle_entity_storage->create($bundle_entity_values);
      $bundle_entity->save();

      // Set mapping entity target bundle.
      $mapping_entity->setTargetBundle($bundle_entity->id());

      // Display message about new bundle entity.
      $t_args = [
        '@type' => $bundle_entity_type_definition->getSingularLabel(),
        '%name' => $bundle_entity->label(),
      ];
      $this->messenger()->addStatus($this->t('The @type %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $bundle_entity->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('node')->notice('Added @type %name.', $context);

      $form_state->setRedirectUrl($bundle_entity->toUrl('collection'));
    }

    $entity_type_id = $this->getTargetEntityTypeId();
    $bundle = $this->getTargetBundle();

    // Reset Schema.org properties.
    // @todo Determine if we only remove existing fields.
    $mapping_entity->set('properties', []);

    // Get properties to fields mapping.
    $new_field_names = [];
    $properties = $form_state->getValue('properties');
    foreach ($properties as $property_name => $property_values) {
      $field_name = $property_values['field']['name'];
      if (!$field_name) {
        continue;
      }

      if (!$this->fieldExists($field_name)) {
        if ($this->fieldStorageExists($field_name)) {
          $property_definition = $this->schemaTypeManager->getProperty($property_name);
          $field = [
            'machine_name' => $field_name,
            'label' => $this->getFieldConfigLabel($field_name) ?: $property_definition['label'],
            'description' => $this->schemaTypeBuilder->formatComment($property_definition['comment']),
            'schema_property' => $property_name,
          ];
          $this->schemaEntityTypeBuilder->addFieldToEntity($entity_type_id, $bundle, $field);
        }
        elseif ($field_name === static::ADD_FIELD) {
          $field = $property_values['field']['add'];
          $field['schema_property'] = $property_name;
          $field['machine_name'] = 'schema_' . $field['machine_name'];
          $field_name = $field['machine_name'];
          if (!$this->fieldExists($field_name)) {
            $this->schemaEntityTypeBuilder->addFieldToEntity($entity_type_id, $bundle, $field);
            $new_field_names[$field_name] = $field['label'];
          }
        }
      }

      $mapping_entity->setSchemaPropertyMapping($field_name, ['property' => $property_name]);
    }

    // Display message about new fields.
    if ($new_field_names) {
      $message = $this->formatPlural(
        count($new_field_names),
        'Added %field_names field',
        'Added %field_names fields',
        ['%field_names' => implode('; ', $new_field_names)]
      );
      $this->messenger()->addStatus($message);
    }

    $this->messenger()->addStatus($this->t('Schema.org mapping has been saved.'));
    $mapping_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Do nothing and allows the entity to be saved via ::submitForm.
  }

  /* ************************************************************************ */
  // Form build methods.
  /* ************************************************************************ */

  /**
   * Build the Schema.org type form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildFieldTypeForm(array &$form) {
    $this->buildEntityTypeForm($form);
    $this->buildSchemaTypeForm($form);
    if ($this->getEntity()->isTargetEntityTypeBundle()) {
      $this->buildAddEntityForm($form);
    }
    $this->buildSchemaPropertiesForm($form);
    $form['#attached']['library'][] = 'schemadotorg_ui/schemadotorg_ui';
    return $form;
  }

  /**
   * Build the entity type summary form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildEntityTypeForm(array &$form) {
    $entity = $this->getEntity();
    $entity_type_bundle = $entity->getTargetEntityBundleEntity();
    if ($entity_type_bundle) {
      $target_entity_type_bundle_definition = $entity->getTargetEntityTypeBundleDefinition();
      $link = $entity_type_bundle->toLink($entity_type_bundle->label(), 'edit-form')->toRenderable();
      $form['entity_type'] = [
        '#type' => 'item',
        '#title' => $target_entity_type_bundle_definition->getLabel(),
        'link' => $link + ['#suffx' => '(' . $entity_type_bundle->id() . ')'],
      ];
    }
    else {
      $target_entity_type_definition = $entity->getTargetEntityTypeDefinition();
      $form['entity_type'] = [
        '#type' => 'item',
        '#title' => $this->t('Entity type'),
        '#markup' => $entity->isTargetEntityTypeBundle()
        ? $target_entity_type_definition->getBundleLabel()
        : $target_entity_type_definition->getLabel(),
      ];
    }
  }

  /**
   * Build the Schema.org type summary form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildSchemaTypeForm(array &$form) {
    $type_definition = $this->getSchmemaTypeDefinition();
    $form['schema_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Schema.org type'),
    ];
    $form['schema_type']['label'] = [
      '#type' => 'link',
      '#title' => $type_definition['label'],
      '#url' => $this->schemaTypeBuilder->getItemUrl($type_definition['label']),
      '#attributes' => ['target' => '_blank'],
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
    $form['schema_type']['comment'] = [
      '#markup' => $this->schemaTypeBuilder->formatComment($type_definition['comment']),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Build the add entity type form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildAddEntityForm(array &$form) {
    $target_entity = $this->getEntity()->getTargetEntityBundleEntity();
    if ($target_entity) {
      return;
    }

    $target_entity_type_bundle_definition = $this->getEntity()->getTargetEntityTypeBundleDefinition();
    $type_definition = $this->getSchmemaTypeDefinition();
    $t_args = ['@name' => $target_entity_type_bundle_definition->getSingularLabel()];
    $form['entity'] = [
      '#type' => 'details',
      '#title' => $this->t('Add @name', $t_args),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['entity']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The human-readable name of this content type. This text will be displayed as part of the list on the Add content page. This name must be unique.'),
      '#required' => TRUE,
      '#default_value' => $type_definition['drupal_label'],
    ];
    $form['entity']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine-readable name'),
      '#description' => $this->t('A unique machine-readable name for this content type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the Add content page.'),
      '#required' => TRUE,
      '#pattern' => '[_0-9a-z]+',
      '#default_value' => $type_definition['drupal_name'],
    ];
    $form['entity']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This text will be displayed on the <em>Add new content</em> page.'),
      '#default_value' => $this->schemaTypeBuilder->formatComment($type_definition['comment']),
    ];
  }

  /**
   * Build Schema.org type properties table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildSchemaPropertiesForm(array &$form) {
    $entity_type_id = $this->getTargetEntityTypeId();
    $field_options = $this->getFieldOptions();
    $property_definitions = $this->getSchemaTypePropertyDefinitions();
    $property_mappings = $this->getSchemaTypePropertyMappings();
    $property_defaults = $this->schemaEntityTypeManager->getSchemaPropertyDefaults($entity_type_id );

    // Header.
    $header = [
      'property' => [
        'data' => $this->t('Property'),
        'width' => '50%',
      ],
      'field' => [
        'data' => $this->t('Field'),
        'width' => '50%',
      ],
    ];

    // Rows.
    $link_options = ['attributes' => ['target' => '_blank']];
    $comment_options = ['attributes' => ['target' => '_blank']];
    $rows = [];
    foreach ($property_definitions as $property => $property_definition) {
      // Skip empty superseded properties.
      if (!empty($property_definition['superseded_by'])
        && empty($property_mappings[$property])) {
        continue;
      }
      $t_args = ['@property' => $property_definition['label']];

      $row = [];

      // Property.
      $row['property'] = [
        '#prefix' => '<div class="schemadotorg-ui-property">',
        '#suffix' => '</div>',
        'label' => [
          '#type' => 'link',
          '#title' => $property_definition['label'],
          '#url' => $this->schemaTypeBuilder->getItemUrl($property_definition['label']),
          '#prefix' => '<div class="schemadotorg-ui-property--label"><strong>',
          '#suffix' => '</strong></div>',
        ],
        'comment' => [
          '#markup' => $this->schemaTypeBuilder->formatComment($property_definition['comment'], $comment_options),
          '#prefix' => '<div class="schemadotorg-ui-property--comment">',
          '#suffix' => '</div>',
        ],
        'range_includes' => [
          'links' => $this->schemaTypeBuilder->buildItemsLinks($property_definition['range_includes'], $link_options),
          '#prefix' => '<div class="schemadotorg-ui-property--range-includes">(',
          '#suffix' => ')</div>',
        ],
      ];

      // Field.
      $field_name = 'schema_' . $property_definition['drupal_name'];
      $field_name_default_value = NULL;
      if (isset($property_mappings[$property])) {
        $field_name_default_value = $property_mappings[$property];
      }
      elseif ($this->getEntity()->isNew()) {
        if ($this->fieldStorageExists($field_name)) {
          $field_name_default_value = $field_name;
        }
        elseif (in_array($property, $property_defaults)) {
          $field_name_default_value = static::ADD_FIELD;
        }
      }
      $row['field'] = [];
      $row['field']['name'] = [
        '#type' => 'select',
        '#title' => $this->t('Field'),
        '#title_display' => 'invisible',
        '#options' => $field_options,
        '#default_value' => $field_name_default_value,
        '#empty_option' => $this->t('- Select or add field -'),
      ];
      $row['field']['add'] = [
        '#type' => 'details',
        '#title' => $this->t('Add field'),
        '#attributes' => ['class' => ['schemadotorg-ui--add-field']],
        '#states' => [
          'visible' => [
            ':input[name="properties[' . $property . '][field][name]"]' => ['value' => static::ADD_FIELD],
          ],
        ],
      ];
      $field_type_options = $this->getSchemaPropertyFieldTypeOptions($property);
      $recommended_category = (string) $this->t('Recommended');
      $field_type_default_value = (isset($field_type_options[$recommended_category]))
        ? array_key_first($field_type_options[$recommended_category])
        : NULL;
      $row['field']['add']['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Field type'),
        '#empty_option' => $this->t('- Select a field type -'),
        '#options' => $field_type_options,
        '#default_value' => $field_type_default_value,
        '#states' => [
          'required' => [
            ':input[name="properties[' . $property . '][field][name]"]' => ['value' => static::ADD_FIELD],
          ],
        ],
      ];
      $row['field']['add']['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#size' => 40,
        '#default_value' => $property_definition['drupal_label'],
        '#states' => [
          'required' => [
            ':input[name="properties[' . $property . '][field][name]"]' => ['value' => static::ADD_FIELD],
          ],
        ],
      ];
      $row['field']['add']['machine_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Machine-readable name'),
        '#descripion' => 'A unique machine-readable name containing letters, numbers, and underscores.',
        '#maxlength' => 26,
        '#size' => 26,
        '#pattern' => '[_0-9a-z]+',
        '#field_prefix' => 'schema_',
        '#default_value' => $property_definition['drupal_name'],
        '#attributes' => ['style' => 'width: 200px'],
        '#wrapper_attributes' => ['style' => 'white-space: nowrap'],
        '#states' => [
          'required' => [
            ':input[name="properties[' . $property . '][field][name]"]' => ['value' => static::ADD_FIELD],
          ],
        ],
      ];
      $row['field']['add']['description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        '#description' => $this->t('Instructions to present to the user below this field on the editing form.'),
        '#default_value' => $this->schemaTypeBuilder->formatComment($property_definition['comment']),
      ];

      $row['field']['add']['unlimited'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unlimited number of values', $t_args),
      ];

      // Highlight mapped properties.
      if ($field_name_default_value) {
        if ($field_name_default_value === static::ADD_FIELD) {
          $row['#attributes'] = ['class' => ['color-warning']];
        }
        else {
          $row['#attributes'] = ['class' => ['color-success']];
        }
      }

      $rows[$property] = $row;
    }

    $form['properties'] = [
      '#type' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
      '#attributes' => ['class' => ['schemadotorg-ui-properties']],
    ] + $rows;
  }

  /**
   * Build the find a Schema.org type form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The find a Schema.org type form.
   */
  protected function buildFindTypeForm(array &$form) {
    // Description top.
    if ($this->moduleHandler->moduleExists('schemadotorg_reports')
      && $this->currentUser()->hasPermission('access site reports')) {
      $t_args = [
        ':type_href' => Url::fromRoute('schemadotorg_reports.types')->toString(),
        ':properties_href' => Url::fromRoute('schemadotorg_reports.properties')->toString(),
        ':things_href' => Url::fromRoute('schemadotorg_reports.types.things')->toString(),
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
    $entity_type_id = $this->getTargetEntityTypeId() ?? 'node';
    $common_types = $this->schemaEntityTypeManager->getCommonSchemaTypes($entity_type_id);
    $items = [];
    foreach ($common_types as $group_name => $group) {
      $item = [];
      $item['group'] = [
        '#markup' => $group['label'],
        '#prefix' => '<strong>',
        '#suffix' => ':</strong> ',
      ];
      foreach ($group['types'] as $type) {
        $item[$type] = $this->buildSchemaTypeItem($type)
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

    $tree = $this->schemaTypeManager->getTypeTree('Thing');
    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Full list of Schema.org types'),
      'tree' => $this->buildTypeTreeRecursive($tree),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTypeTreeRecursive(array $tree) {
    if (empty($tree)) {
      return [];
    }

    $items = [];
    foreach ($tree as $type => $item) {
      $items[$type] = $this->buildSchemaTypeItem($type);
      $children = $item['subtypes'] + $item['enumerations'];
      $items[$type]['children'] = $this->buildTypeTreeRecursive($children);
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * Build Schema.org type item to be displayed in comma or hierarchical lists.
   *
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   A renderable array containing the Schema.org type item.
   */
  protected function buildSchemaTypeItem($type) {
    $schema_mapping_storage = $this->getEntityStorage();
    $entity_type_id = $this->getTargetEntityTypeId();
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

  /* ************************************************************************ */
  // Schema.org methods.
  /* ************************************************************************ */

  /**
   * Get the Schema.org type.
   *
   * @return string|null
   *   The Schema.org type.
   */
  protected function getSchemaType() {
    return $this->getEntity()->getSchemaType();
  }

  /**
   * Get the Schema.org type definition.
   *
   * @return array|false
   *   The Schema.org type definition.
   */
  protected function getSchmemaTypeDefinition() {
    return $this->schemaTypeManager->getType($this->getSchemaType());
  }

  /**
   * Get Schema.org property definitions for the current Schema.org type.
   *
   * @return array
   *   Schema.org property definitions for the current Schema.org type.
   */
  protected function getSchemaTypePropertyDefinitions() {
    $type = $this->getSchemaType();
    $fields = ['label', 'comment', 'range_includes', 'superseded_by', 'drupal_label', 'drupal_name'];
    return $this->schemaTypeManager->getTypeProperties($type, $fields);
  }

  /**
   * Get Schema.org property to field mappings for the current Schema.org type.
   *
   * @return array
   *   Schema.org property to field mappings for the current Schema.org type.
   */
  protected function getSchemaTypePropertyMappings() {
    $mapping_entity = $this->getEntity();
    $mappings = [];

    // For new mapping entities load mapping from existing fields.
    // @todo Rework this to be a lot smarter.
    if ($this->getEntity()->isNew()) {
      $property_definitions = $this->getSchemaTypePropertyDefinitions();
      foreach ($property_definitions as $property => $property_definition) {
        $field_name = 'schema_' . $property_definition['drupal_name'];
        if ($this->fieldExists($field_name)
          || ($mapping_entity->isNewTargetEntityTypeBundle() && $this->fieldStorageExists($field_name))) {
          $mappings[$property] = $field_name;
        }
      }
    }

    // Load mapping from config entity.
    $properties = $mapping_entity->getSchemaProperties();
    foreach ($properties as $field_name => $mapping) {
      $property = $mapping['property'];
      $mappings[$property] = $field_name;
    }

    return $mappings;
  }

  /* ************************************************************************ */
  // Entity methods.
  /* ************************************************************************ */

  /**
   * Get the Schema.org mapping entity.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   *   The Schema.org mapping entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get the Schema.org mapping entity storage.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface
   *   The Schema.org mapping entity storage
   */
  protected function getEntityStorage() {
    return $this->entityTypeManager->getStorage('schemadotorg_mapping');
  }

  /**
   * Get the current entity type ID (i.e. node, block_content, user, etc...).
   *
   * @return string
   *   The current entity type ID
   */
  protected function getTargetEntityTypeId() {
    return $this->getEntity()->getTargetEntityTypeId();
  }

  /**
   * Get the current entity bundle.
   *
   * @return string|null
   *   The current entity bundle.
   */
  protected function getTargetBundle() {
    return $this->getEntity()->getTargetBundle();
  }

  /* ************************************************************************ */
  // Field methods.
  /* ************************************************************************ */

  /**
   * Determine if a field exists for the current entity.
   *
   * @param string $field_name
   *   A field name.
   *
   * @return bool
   *   TRUE if a field exists for the current entity.
   */
  protected function fieldExists($field_name) {
    $entity_type_id = $this->getTargetEntityTypeId();
    $bundle = $this->getTargetBundle();
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    return isset($field_definitions[$field_name]);
  }

  /**
   * Determine if a field storage exists for the current entity.
   *
   * @param string $field_name
   *   A field name.
   *
   * @return bool
   *   TRUE if a field storage exists for the current entity.
   */
  protected function fieldStorageExists($field_name) {
    $entity_type_id = $this->getTargetEntityTypeId();
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    return isset($field_storage_definitions[$field_name]);
  }

  /**
   * Get a field's label from an existing field instance.
   *
   * @param string $field_name
   *   A field name.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   A field's label from an existing field instance.
   */
  protected function getFieldConfigLabel($field_name) {
    $entity_type_id = $this->getTargetEntityTypeId();
    $field_ids = $this->entityTypeManager->getStorage('field_config')->getQuery()
      ->condition('entity_type', $entity_type_id)
      ->condition('field_name', $field_name)
      ->execute();
    if ($field_ids) {
      $field_config = $this->entityTypeManager->getStorage('field_config')
        ->load(reset($field_ids));
      return $field_config->label();
    }
    else {
      return NULL;
    }
  }

  /* ************************************************************************ */
  // Field options methods.
  /* ************************************************************************ */

  /**
   * Get available fields as options.
   *
   * @return array
   *   Available fields as options.
   */
  protected function getFieldOptions() {
    $options = [];
    $options[static::ADD_FIELD] = $this->t('Add a new field…');

    $field_definition_options = $this->getFieldDefinitionsOptions();
    if ($field_definition_options) {
      $options[(string) $this->t('Fields')] = $field_definition_options;
    }

    $base_field_definition_options = $this->getBaseFieldDefinitionsOptions();
    if ($base_field_definition_options) {
      $options[(string) $this->t('Base fields')] = $base_field_definition_options;
    }

    $existing_field_storage_options = $this->getExistingFieldStorageOptions();
    if ($existing_field_storage_options) {
      $options[(string) $this->t('Existing fields')] = $existing_field_storage_options;
    }
    return $options;
  }

  /**
   * Get base fields as options.
   *
   * @return array
   *   Base fields as options.
   */
  protected function getBaseFieldDefinitionsOptions() {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    $entity_type_id = $this->getTargetEntityTypeId();
    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $options = [];

    $base_field_names = $this->schemaEntityTypeManager->getBaseFieldNames($entity_type_id);
    if ($base_field_names) {
      foreach ($base_field_names as $field_name) {
        if (isset($field_definitions[$field_name])) {
          $field_definition = $field_definitions[$field_name];
          $options[$field_definition->getName()] = $this->t('@field (@type)', [
            '@type' => $field_types[$field_definition->getType()]['label'],
            '@field' => $field_definition->getLabel(),
          ]);
        }
      }
    }
    else {
      foreach ($field_definitions as $field_definition) {
        $options[$field_definition->getName()] = $this->t('@field (@type)', [
          '@type' => $field_types[$field_definition->getType()]['label'],
          '@field' => $field_definition->getLabel(),
        ]);
      }
    }

    return $options;
  }

  /**
   * Get the current entity's fields as options.
   *
   * @return array
   *   The current entity's fields as options.
   */
  protected function getFieldDefinitionsOptions() {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    $entity_type_id = $this->getTargetEntityTypeId();
    $bundle = $this->getTargetBundle();
    $field_definitions = array_diff_key(
      $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle),
      $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id)
    );

    $options = [];
    foreach ($field_definitions as $field_definition) {
      $options[$field_definition->getName()] = $this->t('@field (@type)', [
        '@type' => $field_types[$field_definition->getType()]['label'],
        '@field' => $field_definition->getLabel(),
      ]);
    }
    return $options;
  }

  /**
   * Get a Schema.org property's available field types as options.
   *
   * @param string $property
   *   The Schema.org property.
   *
   * @return array[]
   *   A property's available field types as options.
   */
  protected function getSchemaPropertyFieldTypeOptions($property) {
    $recommended_field_types = $this->schemaEntityTypeManager->getSchemaPropertyFieldTypes($property);
    $recommended_category = (string) $this->t('Recommended');

    $options = [$recommended_category => []];

    // Collecting found field type to ensure the field type is installed.
    $grouped_definitions = $this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions());
    foreach ($grouped_definitions as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        if (isset($recommended_field_types[$name])) {
          $options[$recommended_category][$name] = $field_type['label'];
        }
        else {
          $options[$category][$name] = $field_type['label'];
        }
      }
    }
    if (empty($options[$recommended_category])) {
      unset($options[$recommended_category]);
    }
    else {
      // @see https://stackoverflow.com/questions/348410/sort-an-array-by-keys-based-on-another-array#answer-9098675
      $recommended_field_types = array_intersect_key($recommended_field_types, $options[$recommended_category]);
      $options[$recommended_category] = array_replace($recommended_field_types, $options[$recommended_category]);
    }
    return $options;
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @return array
   *   An array of existing field storages keyed by name.
   *
   * @see \Drupal\field_ui\Form\FieldStorageAddForm::getExistingFieldStorageOptions
   */
  protected function getExistingFieldStorageOptions() {
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    // Load the field_storages and build the list of options.
    $options = [];
    foreach ($this->entityFieldManager->getFieldStorageDefinitions($this->getTargetEntityTypeId()) as $field_name => $field_storage) {
      // Do not show:
      // - non-configurable field storages,
      // - locked field storages,
      // - field storages that should not be added via user interface,
      // - field storages that already have a field in the bundle.
      $field_type = $field_storage->getType();
      if ($field_storage instanceof FieldStorageConfigInterface
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])
        && !in_array($this->getTargetBundle(), $field_storage->getBundles(), TRUE)) {
        $options[$field_name] = $this->t('@field (@type)', [
          '@type' => $field_types[$field_type]['label'],
          '@field' => $field_name,
        ]);
      }
    }
    asort($options);

    return $options;
  }

}
