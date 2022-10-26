<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_field_group;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field_group\Form\FieldGroupAddForm;
use Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;

/**
 * Schema.org field group entity display builder service.
 */
class SchemaDotOrgFieldGroupEntityDisplayBuilder implements SchemaDotOrgFieldGroupEntityDisplayBuilderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Schema.org names service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface
   */
  protected $schemaNames;

  /**
   * The Schema.org entity display builder service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface
   */
  protected $schemaEntityDisplayBuilder;

  /**
   * Constructs a SchemaDotOrgFieldGroupEntityDisplayBuilder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityDisplayBuilderInterface $schema_entity_display_builder
   *   The Schema.org entity display builder service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    SchemaDotOrgNamesInterface $schema_names,
    SchemaDotOrgEntityDisplayBuilderInterface $schema_entity_display_builder
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $display_repository;
    $this->schemaNames = $schema_names;
    $this->schemaEntityDisplayBuilder = $schema_entity_display_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldGroups(string $entity_type_id, string $bundle, string $schema_type, array $properties): void {
    // Form display.
    $form_modes = $this->schemaEntityDisplayBuilder->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      foreach ($properties as $field_name => $property) {
        $this->setFieldGroup($form_display, $field_name, $schema_type, $property);
      }
      $form_display->save();
    }

    // View display.
    $view_modes = $this->schemaEntityDisplayBuilder->getViewModes($entity_type_id, $bundle);
    // Only support field groups in the default and full view modes.
    $view_modes = array_intersect_key($view_modes, ['default' => 'default', 'full' => 'full']);
    foreach ($view_modes as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      foreach ($properties as $field_name => $property) {
        $this->setFieldGroup($view_display, $field_name, $schema_type, $property);
      }
      $view_display->save();
    }
  }

  /**
   * Set entity display field groups for a Schema.org property.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $schema_type
   *   The field name's associated Schema.org type.
   * @param string $schema_property
   *   The field name's associated Schema.org property.
   *
   * @see field_group_group_save()
   * @see field_group_field_overview_submit()
   * @see \Drupal\field_group\Form\FieldGroupAddForm::submitForm
   */
  protected function setFieldGroup(EntityDisplayInterface $display, string $field_name, string $schema_type, string $schema_property): void {
    if (!$this->hasFieldGroup($display, $field_name, $schema_type, $schema_property)) {
      return;
    }

    $entity_type_id = $display->getTargetEntityTypeId();
    $display_type = ($display instanceof EntityFormDisplayInterface) ? 'form' : 'view';

    $config = $this->configFactory->get('schemadotorg_field_group.settings');
    $default_field_groups = $config->get('default_field_groups.' . $entity_type_id) ?? [];
    $default_label_suffix = $config->get('default_label_suffix');
    $default_format_type = $config->get('default_' . $display_type . '_type') ?: '';
    $default_format_settings = ($default_format_type === 'details') ? ['open' => TRUE] : [];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $mapping_type */
    $mapping_type = $mapping_type_storage->load($entity_type_id);

    $default_field_weights = $this->schemaEntityDisplayBuilder->getDefaultFieldWeights();

    $group_weight = 0;
    $group_name = NULL;
    $group_label = NULL;
    $field_weight = NULL;
    $index = -5;
    foreach ($default_field_groups as $default_field_group_name => $default_field_group) {
      $properties = array_flip($default_field_group['properties']);
      if (isset($properties[$schema_property])) {
        $group_name = $default_field_group_name;
        $group_label = $default_field_group['label'];
        $group_weight = $index;
        $field_weight = $properties[$schema_property];
        break;
      }
      $index++;
    }

    // Automatically generate a default catch all field group for
    // the Schema.org type.
    if (!$group_name) {
      // But don't generate a group for default fields.
      $base_field_names = $mapping_type->getBaseFieldNames();
      if (isset($base_field_names[$field_name])) {
        return;
      }

      $group_name = $this->schemaNames->schemaIdToDrupalName('types', $schema_type);
      $group_label = $this->schemaNames->camelCaseToSentenceCase($schema_type);
      if ($default_label_suffix) {
        $group_label .= ' ' . $default_label_suffix;
      }
      if (isset($default_field_weights[$schema_property])) {
        $field_weight = $default_field_weights[$schema_property];
      }
      elseif (!empty($default_field_weights)) {
        $field_weight = max($default_field_weights);
      }
      else {
        $field_weight = 0;
      }
    }

    // Prefix group name.
    $group_name = FieldGroupAddForm::GROUP_PREFIX . $group_name;

    // Remove field name from an existing groups, so that it can be reset.
    $existing_groups = $display->getThirdPartySettings('field_group');
    foreach ($existing_groups as $existing_group_name => $existing_group) {
      $index = array_search($field_name, $existing_group['children']);
      if ($index !== FALSE) {
        array_splice($existing_group['children'], $index, 1);
        $display->setThirdPartySetting('field_group', $existing_group_name, $existing_group);
      }
    }

    // Get existing group.
    $group = $display->getThirdPartySetting('field_group', $group_name);
    if (!$group) {
      $group = [
        'label' => $group_label,
        'children' => [],
        'parent_name' => '',
        'weight' => $group_weight,
        'format_type' => $default_format_type,
        'format_settings' => $default_format_settings,
        'region' => 'content',
      ];
    }

    // Append the field to the children.
    $group['children'][] = $field_name;
    $group['children'] = array_unique($group['children']);

    // Set field group in the entity display.
    $display->setThirdPartySetting('field_group', $group_name, $group);

    // Set field component's weight.
    $component = $display->getComponent($field_name);
    $component['weight'] = $field_weight;
    $display->setComponent($field_name, $component);
  }

  /**
   * Determine if the Schema.org property/field name has field group.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $schema_type
   *   The field name's associated Schema.org type.
   * @param string $schema_property
   *   The field name's associated Schema.org property.
   *
   * @return bool
   *   TRUE if the Schema.org property/field name has field group
   */
  protected function hasFieldGroup(EntityDisplayInterface $display, string $field_name, string $schema_type, string $schema_property): bool {
    if (!$display->getComponent($field_name)) {
      return FALSE;
    }

    $disable_field_groups = $this->configFactory
      ->get('schemadotorg_field_group.settings')
      ->get('disable_field_groups');
    if (empty($disable_field_groups)) {
      return TRUE;
    }

    $entity_type_id = $display->getTargetEntityTypeId();
    $display_type = ($display instanceof EntityFormDisplayInterface) ? 'form' : 'view';
    $display_mode = $display->getMode();

    $disabled_patterns = [
      $entity_type_id,
      "$entity_type_id--$display_type",
      "$entity_type_id--$display_type--$schema_type",
      "$entity_type_id--$display_type--$schema_type--$schema_property",
      "$entity_type_id--$display_type--$schema_property",
      "$entity_type_id--$display_type--$display_mode",
      "$entity_type_id--$display_type--$display_mode--$schema_type",
      "$entity_type_id--$display_type--$display_mode--$schema_type--$schema_property",
      "$entity_type_id--$display_type--$display_mode--$schema_property",
      "$entity_type_id--$schema_type",
      "$entity_type_id--$schema_type--$schema_property",
      "$entity_type_id--$schema_property",
    ];

    $disabled = (bool) array_intersect($disable_field_groups, $disabled_patterns);
    return !$disabled;
  }

}
