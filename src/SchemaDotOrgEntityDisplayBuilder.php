<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field_group\Form\FieldGroupAddForm;

/**
 * Schema.org entity display builder service.
 */
class SchemaDotOrgEntityDisplayBuilder implements SchemaDotOrgEntityDisplayBuilderInterface {

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
   * Constructs a SchemaDotOrgBuilder object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names
   *   The Schema.org names service.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $display_repository,
    SchemaDotOrgNamesInterface $schema_names
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $display_repository;
    $this->schemaNames = $schema_names;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldDisplays(array $field_values, $widget_id, array $widget_settings, $formatter_id, array $formatter_settings) {
    $entity_type_id = $field_values['entity_type'];
    $bundle = $field_values['bundle'];
    $field_name = $field_values['field_name'];

    // Form display.
    $form_modes = $this->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      $this->setComponent($form_display, $field_name, $widget_id, $widget_settings);
      $form_display->save();
    }

    // View display.
    $view_modes = $this->getViewModes($entity_type_id, $bundle);
    foreach ($view_modes as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      $this->setComponent($view_display, $field_name, $formatter_id, $formatter_settings);
      $view_display->save();
    }
  }

  /**
   * Get display form modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   An array of display form modes.
   */
  protected function getFormModes($entity_type_id, string $bundle) {
    return $this->getModes(
      $entity_type_id,
      $bundle,
      'Form',
      []
    );
  }

  /**
   * Get display view modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   An array of display view modes.
   */
  protected function getViewModes($entity_type_id, string $bundle) {
    $default_view_modes = ['teaser', 'content_browser'];
    return $this->getModes(
      $entity_type_id,
      $bundle,
      'View',
      $default_view_modes
    );
  }

  /**
   * Get display modes for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $type
   *   The display modes.
   * @param array $default_modes
   *   An array of default display modes.
   *
   * @return array
   *   An array of display modes.
   */
  protected function getModes($entity_type_id, $bundle, $type = 'View', $default_modes = []) {
    $mode_method = "get{$type}ModeOptionsByBundle";
    $mode_options = $this->entityDisplayRepository->$mode_method($entity_type_id, $bundle);

    if ($default_modes) {
      $modes = array_intersect_key(
        array_combine($default_modes, $default_modes),
        $mode_options
      );
    }
    else {
      $mode_keys = array_keys($mode_options);
      $modes = array_combine($mode_keys, $mode_keys);
    }

    return ['default' => 'default'] + $modes;
  }

  /**
   * Set entity display component.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $type
   *   The component's plugin id.
   * @param array $settings
   *   The component's plugin settings.
   */
  protected function setComponent(EntityDisplayInterface $display, $field_name, $type, array $settings) {
    // Only add the 'body' to 'teaser' and 'content_browser' view modes
    // for node types.
    // @see node_add_body_field()
    if ($this->isNodeTeaserDisplay($display)) {
      if ($field_name !== 'body') {
        $display->removeComponent($field_name);
        return;
      }
      $settings = [
        'label' => 'hidden',
        'type' => 'text_summary_or_trimmed',
      ];
    }

    $options = [];
    if ($type) {
      $options['type'] = $type;
      if (!empty($settings)) {

        if (isset($settings['third_party_settings'])) {
          $options['third_party_settings'] = $settings['third_party_settings'];
          unset($settings['third_party_settings']);
        }
        $options['settings'] = $settings;
      }
    }

    // Custom weights.
    $entity_type_id = $display->getTargetEntityTypeId();
    switch ($entity_type_id) {
      case 'media':
        $options['weight'] = 10;
        break;
    }

    $display->setComponent($field_name, $options);
  }

  /**
   * Set entity display field weights for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $properties
   *   The Schema.org properties to be weighted.
   */
  public function setFieldWeights($entity_type_id, $bundle, array $properties) {
    // Form display.
    $form_modes = $this->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      foreach ($properties as $field_name => $property) {
        $this->setFieldWeight($form_display, $field_name, $property);
      }
      $form_display->save();
    }

    // View display.
    $view_modes = $this->getViewModes($entity_type_id, $bundle);
    foreach ($view_modes as $view_mode) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_mode);
      foreach ($properties as $field_name => $property) {
        $this->setFieldWeight($view_display, $field_name, $property);
      }
      $view_display->save();
    }
  }

  /**
   * Set entity display field weight for a Schema.org property.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   * @param string $field_name
   *   The field name to be set.
   * @param string $schema_property
   *   The field name's associated Schema.org property.
   */
  protected function setFieldWeight(EntityDisplayInterface $display, $field_name, $schema_property) {
    // Make sure the field component exists.
    if (!$display->getComponent($field_name)) {
      return;
    }

    $entity_type_id = $display->getTargetEntityTypeId();

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $mapping_type = $mapping_type_storage->load($entity_type_id);
    $default_field_weights = $mapping_type->getDefaultFieldWeights();
    if (empty($default_field_weights)) {
      return;
    }

    // Use the property's default field weight or the lowest weight plus one.
    $field_weight = $default_field_weights[$schema_property] ?? max($default_field_weights) + 1;

    $component = $display->getComponent($field_name);
    $component['weight'] = $field_weight;
    $display->setComponent($field_name, $component);
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldGroups($entity_type_id, $bundle, $schema_type, array $properties) {
    // Make sure the field group module is enabled.
    if (!$this->moduleHandler->moduleExists('field_group')) {
      return;
    }

    // Form display.
    $form_modes = $this->getFormModes($entity_type_id, $bundle);
    foreach ($form_modes as $form_mode) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $form_mode);
      foreach ($properties as $field_name => $property) {
        $this->setFieldGroup($form_display, $field_name, $schema_type, $property);
      }
      $form_display->save();
    }

    // View display.
    $view_modes = $this->getViewModes($entity_type_id, $bundle);
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
  protected function setFieldGroup(EntityDisplayInterface $display, $field_name, $schema_type, $schema_property) {
    // Make sure the field component exists.
    if (!$display->getComponent($field_name)) {
      return;
    }

    // Do not use field groups via node teaser display.
    if ($this->isNodeTeaserDisplay($display)) {
      return;
    }

    $entity_type_id = $display->getTargetEntityTypeId();

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $mapping_type */
    $mapping_type = $mapping_type_storage->load($entity_type_id);
    $default_field_groups = $mapping_type->getDefaultFieldGroups();
    $default_label_suffix = $mapping_type->getDefaultFieldGroupLabelSuffix();
    $default_format_type = $mapping_type->getDefaultFieldGroupFormatType($display);
    $default_format_settings = $mapping_type->getDefaultFieldGroupFormatSettings($display);
    $default_field_weights = $mapping_type->getDefaultFieldWeights();
    if (empty($default_field_groups) && empty($default_format_type)) {
      return;
    }

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
      $field_weight = $default_field_weights[$schema_property]
        ?? max($default_field_weights);
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
   * Determine if a display is node teaser view display.
   *
   * @todo Determine if should be a configurable behavior.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display.
   *
   * @return bool
   *   TRUE if the display is node teaser view display.
   *
   * @see node_add_body_field()
   */
  protected function isNodeTeaserDisplay(EntityDisplayInterface $display) {
    $entity_type_id = $display->getTargetEntityTypeId();
    $mode = $display->getMode();
    if ($mode !== EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE
      && $display instanceof EntityViewDisplayInterface
      && $entity_type_id === 'node'
      && in_array($mode, ['teaser', 'content_browser'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
