<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Schema.org entity type manager.
 */
class SchemaDotOrgEntityTypeManager implements SchemaDotOrgEntityTypeManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * Constructs a SchemaDotOrgEntityTypeManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * Get entity types that implement Schema.org.
   *
   * @return array
   *   Entity types that implement Schema.org.
   */
  public function getEntityTypes() {
    return [
      'block_content',
      'node',
      'media',
      'paragraph',
      'user',
    ];
  }

  public function getBaseFieldNames() {
    return [
      'uuid',
      'revision_uid',
      'uid',
      'title',
      'created',
      'changed',
      'promote',
      'sticky',
      'path',
    ];
  }

  public function getEntitySchemaType($entity_type_id, $bundle = NULL) {
    // @todo Determine best to map user entity type to Person schema type.
    if ($entity_type_id === 'user') {
      return 'Person';
    }

    $entity = $this->getEntity($entity_type_id, $bundle);
    return $entity
      ? $entity->getThirdPartySetting('schemadotorg', 'type')
      : NULL;
  }

  public function getEntity($entity_type_id, $bundle = NULL) {
    $field_definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_entity_type_id = $field_definition->getBundleEntityType();
    if (!$bundle_entity_type_id) {
      return NULL;
    }

    $entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
    return $entity_storage->load($bundle) ?: NULL;
  }
  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyFieldTypes($property) {
    $property_mappings = [
      'description' => ['text_long', 'text', 'text_with_summary'],
      'disambiguatingDescription' => ['text_long', 'text', 'text_with_summary'],
      'identifier' => ['key_value', 'key_value_long'],
      'image' => ['field_ui:entity_reference:media', 'image'],
      'telephone' => ['telephone'],
    ];

    $data_type_mappings = [
      // Data types.
      'Text' => ['string', 'string_long', 'list_string', 'text', 'text_long', 'text_with_summary'],
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

    $property_definition = $this->schemaTypeManager->getProperty($property);

    // Set property specific field types.
    $field_types = [];
    if (isset($property_mappings[$property])) {
      $field_types = array_merge($field_types, $property_mappings[$property]);
    }

    // Set range include field types.
    $range_includes = $this->schemaTypeManager->parseIds($property_definition['range_includes']);
    foreach ($range_includes as $range_include) {
      if (isset($data_type_mappings[$range_include])) {
        $field_types = array_merge($field_types, $data_type_mappings[$range_include]);
      }
    }

    // Set a default field type.
    if (!$field_types) {
      $field_types[] = 'entity_reference';
    }

    return $field_types;
  }

  public function getEntityFieldProperties(EntityInterface $entity = NULL) {
    if (!$entity) {
      return [];
    }
    /** @var EntityFieldManager $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $field_properties = [];

    // Set entity label.
    $definition = \Drupal::entityTypeManager()->getDefinition($entity->getEntityTypeId());
    $target_entity_type_id = $definition->getBundleOf();
    $target_bundle = $entity->id();

    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($target_entity_type_id);
    foreach ($field_storage_definitions as $field_storage_definition) {
      if (!$field_storage_definition instanceof FieldStorageConfigInterface) {
        continue;
      }

      $property = $field_storage_definition->getThirdPartySetting('schemadotorg', 'property');
      if ($property) {
        $field_properties[$property] = [
          'machine_name' => $field_storage_definition->getName(),
          'type' => $field_storage_definition->getType(),
          'unlimited' => ($field_storage_definition->getCardinality() === -1),
        ];
      }
      $entity_field_manager->getFieldDefinitions($target_entity_type_id, $target_bundle);
    }

    $field_definitions = $entity_field_manager->getFieldDefinitions($target_entity_type_id, $target_bundle);
    foreach ($field_definitions as $field_definition) {
      if (!$field_definition instanceof FieldConfigInterface) {
        continue;
      }

      $property = $field_definition->getFieldStorageDefinition()->getThirdPartySetting('schemadotorg', 'property');
      if ($property) {
        $field_properties += [$property => []];
        $field_properties[$property] += [
          'status' => TRUE,
          'label' => $field_definition->getLabel(),
          'required' => $field_definition->isRequired(),
        ];
      }
    }
    return $field_properties;
  }

}
