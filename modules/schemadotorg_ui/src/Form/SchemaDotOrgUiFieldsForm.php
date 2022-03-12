<?php

namespace Drupal\schemadotorg_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Schema.org UI form.
 *
 * @see \Drupal\field_ui\Form\FieldStorageAddForm
 */
class SchemaDotOrgUiFieldsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager
   */
  protected $schemaEntityTypeManager;

  /**
   * The Schema.org entity type builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder
   */
  protected $schemaEntityTypeBuilder;

  /**
   * The current entity type id (i.e. node_type, media_type, user, etc...)
   *
   * @var string|null
   */
  protected $entityTypeId;

  /**
   * The current entity bundle id (i.e. page, image, etc...)
   *
   * @var string|null
   */
  protected $bundle;

  /**
   * The current Schema.org type.
   *
   * @var string|null
   */
  protected $type;

  /**
   * The current (bundle) entity.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityBundleBase
   */
  protected $entity;

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

    $instance->entityTypeManager = $container->get('entity_type.manager');
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
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;

    if ($this->isBundleEntityType()) {
      $bundle_entity_type_id = $this->getBundleEntityTypeId();
      $entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
      $this->entity = $entity_storage->load($bundle);
    }

    $this->type = $this->schemaEntityTypeManager->getEntitySchemaType($entity_type_id, $bundle)
      ?: $this->getRequest()->query->get('type');

    if ($this->type) {
      return $this->buildFieldTypeForm($form, $form_state);
    }
    else {
      return $this->buildFindTypeForm($form);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo Validate the entity.

    // @todo Validate the properties.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $op = (string) $form_state->getValue('op');

    if ($op === (string) $this->t('Find')) {
      $type = $form_state->getValue('type');
      $form_state->setRedirect('<current>', [], ['query' => ['type' => $type]]);
      return;
    }

    $schema_type_id = $this->getSchemaType();

    // Create the bundle entity.
    if ($this->isNew()) {
      $entity_values = $form_state->getValue('entity');

      $bundle_entity_type_id = $this->getBundleEntityTypeId();
      $bundle_entity_type_definition = $this->getBundleEntityTypeDefinition();

      $id_key = $bundle_entity_type_definition->getKey('id');
      $label_key = $bundle_entity_type_definition->getKey('label');

      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_storage */
      $entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
      $entity = $entity_storage->create()
        ->set($id_key, $entity_values['id'])
        ->set($label_key, $entity_values['label']);

      $this->bundle = $entity_values['id'];

      $t_args = [
        '@type' => $bundle_entity_type_definition->getSingularLabel(),
        '%name' => $entity_values['label'],
      ];
      $this->messenger()->addStatus($this->t('The @type %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $entity->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('node')->notice('Added @type %name.', $context);

      $form_state->setRedirectUrl($entity->toUrl('collection'));
    }

    // Set Schema.org type.
    // @todo move to config entity.
    if ($this->getEntity()) {
      $this->getEntity()
        ->setThirdPartySetting('schemadotorg', 'type', $schema_type_id)
        ->save();
    }

    $entity_type_id = $this->getEntityTypeId();
    $bundle = $this->getBundle();

    // Get properties to fields mapping.
    $new_field_names = [];
    $property_mapping = [];
    $properties = $form_state->getValue('properties');
    foreach ($properties as $property_name => $property_values) {
      $field_name = $property_values['field']['name'];
      if (!$field_name) {
        continue;
      }

      if (!$this->fieldExists($field_name)) {
        if ($this->fieldStorageExists($field_name)) {
          $field_label = $this->getFieldConfigLabel($field_name)
            ?: $this->getSchemaPropertyLabel($property_name);
          $field = ['machine_name' => $field_name, 'label' => $field_label];
          $this->schemaEntityTypeBuilder->addFieldToEntity($entity_type_id, $bundle, $field);
        }
        elseif ($field_name === '_add_') {
          $field = $property_values['field']['add'];
          $field['machine_name'] = 'schema_' . $field['machine_name'];
          $field_name = $field['machine_name'];
          if (!$this->fieldExists($field_name)) {
            $this->schemaEntityTypeBuilder->addFieldToEntity($entity_type_id, $bundle, $field);
            $new_field_names[$field_name] = $field['label'];
          }
        }
      }

      $property_mapping[$field_name] = $property_name;
    }

    if ($new_field_names) {
      $message = $this->formatPlural(
        count($new_field_names),
        'Added %field_names field',
        'Added %field_names fields',
        ['%field_names' => implode('; ', $new_field_names)]
      );
      $this->messenger()->addStatus($message);
    }
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
    $this->buildSchemaTypeForm($form);
    if ($this->isBundleEntityType()) {
      $this->buildAddEntityForm($form);
    }
    $this->buildSchemaPropertiesForm($form);
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    ];
    $form['#attached']['library'][] = 'schemadotorg_ui/schemadotorg_ui';
    return $form;
  }

  /**
   * Build the Schema.org type summary form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildSchemaTypeForm(array &$form) {
    $type_definition = $this->getSchmemaTypeDefinition();
    $form['type'] = [
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
  }

  /**
   * Build the add entity type form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildAddEntityForm(array &$form) {
    $entity = $this->getEntity();
    if ($entity) {
      return;
    }

    $bundle_entity_type = $this->getBundleEntityTypeDefinition();
    $type_definition = $this->getSchmemaTypeDefinition();
    $t_args = ['@name' => $bundle_entity_type->getSingularLabel()];
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
  }

  /**
   * Build Schema.org type properties table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function buildSchemaPropertiesForm(array &$form) {
    $field_options = $this->getFieldOptions();
    $property_definitions = $this->getSchemaTypePropertyDefinitions();
    $property_mappings = $this->getSchemaTypePropertyMappings();

    // Header.
    $header = [
      'property' => [
        'data' => $this->t('Property'),
        'width' => '60%',
      ],
      'field' => [
        'data' => $this->t('Field'),
        'width' => '40%',
      ],
    ];

    // Rows.
    $rows = [];
    foreach ($property_definitions as $property => $property_definition) {
      $t_args = ['@property' => $property_definition['label']];
      $row = [];

      // Property.
      $row['property'] = [
        '#prefix' => '<div class="schemadotorg-ui-property">',
        '#suffix' => '</div>',
        'label' => [
          '#markup' => $property_definition['label'],
          '#prefix' => '<div class="schemadotorg-ui-property--label"><strong>',
          '#suffix' => '</strong></div>',
        ],
        'comment' => [
          '#markup' => $this->schemaTypeBuilder->formatComment($property_definition['comment']),
          '#prefix' => '<div class="schemadotorg-ui-property--comment">',
          '#suffix' => '</div>',
        ],
        'range_includes' => [
          'links' => $this->schemaTypeBuilder->buildItemsLinks($property_definition['range_includes']),
          '#prefix' => '<div class="schemadotorg-ui-property--range-includes">(',
          '#suffix' => ')</div>',
        ],
      ];

      // Field.
      $row['field'] = [];
      $row['field']['name'] = [
        '#type' => 'select',
        '#title' => $this->t('Field'),
        '#title_display' => 'invisible',
        '#options' => $field_options,
        '#default_value' => $property_mappings[$property] ?? NULL,
        '#empty_option' => $this->t('- Select or add field -'),
      ];
      $row['field']['add'] = [
        '#type' => 'details',
        '#title' => $this->t('Add field'),
        '#open' => TRUE,
        '#attributes' => ['class' => ['schemadotorg-ui--add-field']],
        '#states' => [
          'visible' => [
            ':input[name="properties[' . $property . '][field][name]"]' => ['value' => '_add_'],
          ],
          'required' => [
            ':input[name="properties[' . $property . '][field][name]"]' => ['value' => '_add_'],
          ],
        ],
      ];
      $row['field']['add']['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $property_definition['drupal_label'],
      ];
      $row['field']['add']['machine_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Machine-readable name'),
        '#descripion' => 'A unique machine-readable name containing letters, numbers, and underscores.',
        '#maxlength' => 26,
        '#pattern' => '[_0-9a-z]+',
        '#field_prefix' => 'schema_',
        '#default_value' => $property_definition['drupal_name'],
        '#attributes' => ['style' => 'width: 200px'],
        '#wrapper_attributes' => ['style' => 'white-space: nowrap'],
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
      ];
      $row['field']['add']['unlimited'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unlimited number of values', $t_args),
      ];

      // Highlight mapped properties.
      if (isset($property_mappings[$property])) {
        $row['#attributes'] = ['class' => ['color-success']];
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
    return $this->type;
  }

  /**
   * Get the Schema.org type definition.
   *
   * @return array|false
   *   The Schema.org type definition.
   */
  protected function getSchmemaTypeDefinition() {
    return $this->schemaTypeManager->getType($this->type);
  }

  /**
   * Get a Schema.org property's label.
   *
   * @param string $property
   *   A a Schema.org property.
   *
   * @return string
   *   a Schema.org property's label.
   */
  protected function getSchemaPropertyLabel($property) {
    $property_definition = $this->schemaTypeManager->getProperty($property);
    return $property_definition['drupal_label'];
  }

  /**
   * Get Schema.org property definitions for the current Schema.org type.
   *
   * @return array
   *   Schema.org property definitions for the current Schema.org type.
   */
  protected function getSchemaTypePropertyDefinitions() {
    $type = $this->getSchemaType();
    $fields = ['label', 'comment', 'range_includes', 'drupal_label', 'drupal_name'];
    return $this->schemaTypeManager->getTypeProperties($type, $fields);
  }

  /**
   * Get Schema.org property to field mappings for the current Schema.org type.
   *
   * @return array
   *   Schema.org property to field mappings for the current Schema.org type.
   */
  protected function getSchemaTypePropertyMappings() {
    $property_definitions = $this->getSchemaTypePropertyDefinitions();
    $mappings = [];
    // @todo Load mapping from config entity.
    foreach ($property_definitions as $property => $property_definition) {
      $field_name = 'schema_' . $property_definition['drupal_name'];
      if ($this->fieldExists($field_name)
        || ($this->isNew() && $this->fieldStorageExists($field_name))) {
        $mappings[$property] = $field_name;
      }
    }
    return $mappings;
  }

  /* ************************************************************************ */
  // Entity methods.
  /* ************************************************************************ */

  /**
   * Get the current bundle/type entity, when applicable.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityBundleBase|null
   *   The current bundle/type entity
   */
  protected function getEntity() {
    return $this->entity;
  }

  /**
   * Get the current entity type ID (i.e. node, block_content, user, etc...).
   *
   * @return string
   *   The current entity type ID
   */
  protected function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * Get the current entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The current entity type.
   */
  protected function getEntityType() {
    return $this->entityTypeManager->getDefinition($this->entityTypeId);
  }

  /**
   * Get the current entity bundle.
   *
   * @return string|null
   *   The current entity bundle.
   */
  protected function getBundle() {
    return $this->isBundleEntityType() ? $this->bundle : $this->entityTypeId;
  }

  /**
   * Get the current bundle entity type ID.
   *
   * @return string|null
   *   The current bundle entity type ID.
   */
  protected function getBundleEntityTypeId() {
    return $this->getEntityType()->getBundleEntityType();
  }

  /**
   * Get the current bundle entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   Get the current bundle entity type.
   */
  protected function getBundleEntityTypeDefinition() {
    $bundle_entity_type = $this->getBundleEntityTypeId();
    return $this->entityTypeManager->getDefinition($bundle_entity_type);
  }

  /**
   * Determine if the current entity support bundling.
   *
   * @return bool
   *   TRUE if the current entity support bundling.
   */
  protected function isBundleEntityType() {
    return (boolean) $this->getBundleEntityTypeId();
  }

  /**
   * Determine if a new bundle entity is being created.
   *
   * @return bool
   *   TRUE if a new bundle entity is being created.
   */
  protected function isNew() {
    return ($this->isBundleEntityType() && !$this->getEntity());
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
    $entity_type_id = $this->getEntityTypeId();
    $bundle = $this->getBundle();
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
    $entity_type_id = $this->getEntityTypeId();
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
    $entity_type_id = $this->getEntityTypeId();
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
    $options['_add_'] = $this->t('Add field…');

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
    $entity_type_id = $this->getEntityTypeId();
    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $options = [];

    $base_field_names = $this->schemaEntityTypeManager->getBaseFieldNames($entity_type_id);
    if ($base_field_names) {
      foreach ($base_field_names as $field_name) {
        if (isset($field_definitions[$field_name])) {
          $field_definition = $field_definitions[$field_name];
          $options[$field_definition->getName()] = $field_definition->getLabel();
        }
      }
    }
    else {
      foreach ($field_definitions as $field_definition) {
        $options[$field_definition->getName()] = $field_definition->getLabel();
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
    $entity_type_id = $this->getEntityTypeId();
    $bundle = $this->getBundle();
    $field_definitions = array_diff_key(
      $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle),
      $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id)
    );

    $options = [];
    foreach ($field_definitions as $field_definition) {
      $options[$field_definition->getName()] = $field_definition->getLabel();
    }
    return $options;
  }

  /**
   * Get a Schema.org property's available field types as options.
   *
   * @param string $property
   *   A Schema.org property.
   *
   * @return array[]
   *   A property's available field types as options.
   */
  protected function getSchemaPropertyFieldTypeOptions($property) {
    $recommended_field_types = $this->schemaEntityTypeManager->getSchemaPropertyFieldTypes($property);
    $recommended_category = (string) $this->t('Recommended');

    $options = [$recommended_category => []];

    $grouped_definitions = $this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions());
    foreach ($grouped_definitions as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        if (in_array($name, $recommended_field_types)) {
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
      $options[$recommended_category] = array_replace(array_flip($recommended_field_types), $options[$recommended_category]);
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
    $options = [];
    // Load the field_storages and build the list of options.
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    foreach ($this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId) as $field_name => $field_storage) {
      // Do not show:
      // - non-configurable field storages,
      // - locked field storages,
      // - field storages that should not be added via user interface,
      // - field storages that already have a field in the bundle.
      $field_type = $field_storage->getType();
      if ($field_storage instanceof FieldStorageConfigInterface
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])
        && !in_array($this->bundle, $field_storage->getBundles(), TRUE)) {
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

