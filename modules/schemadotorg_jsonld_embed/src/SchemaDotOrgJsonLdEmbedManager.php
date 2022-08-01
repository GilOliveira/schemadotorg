<?php

namespace Drupal\schemadotorg_jsonld_embed;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;

/**
 * Schema.org JSON-LD embed manager.
 */
class SchemaDotOrgJsonLdEmbedManager implements SchemaDotOrgJsonLdEmbedInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $schemaJsonLdBuilder;

  /**
   * Xpath selector for finding embedded media.
   *
   * @var string
   */
  protected $xpath = 'descendant-or-self::*[(@data-entity-type) and (@data-entity-uuid)]';

  /**
   * Constructs a SchemaDotOrgJsonLdEmbedManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface|null $schema_jsonld_builder
   *   The Schema.org JSON-LD builder service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SchemaDotOrgJsonLdBuilderInterface $schema_jsonld_builder = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaJsonLdBuilder = $schema_jsonld_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $entity) {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    $mapping = $mapping_storage->loadByEntity($entity);
    if (!$mapping) {
      return [];
    }

    // Make sure the entity's values includes the [data-entity-type] attribute.
    $text = print_r($entity->toArray(), TRUE);
    if (strpos($text, 'data-entity-type') === FALSE) {
      return [];
    }

    $data = [];
    $schema_properties = $mapping->getSchemaProperties();
    foreach ($schema_properties as $field_name => $schema_property) {
      // Make sure the entity has the field and the current user has
      // access to the field.
      if (!$entity->hasField($field_name) || !$entity->get($field_name)->access('view')) {
        continue;
      }

      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $entity->get($field_name);
      $field_type = $items->getFieldDefinition()->getType();
      if (in_array($field_type, ['text_long', 'text_with_summary'])) {
        foreach ($items as $item) {
          $data += $this->getEntitiesData($item->value);
        }
      }
    }
    return $data;
  }

  /**
   * Get embedded media and content JSON-LD data from a text value.
   *
   * @param string $value
   *   The text/HTML value.
   *
   * @return array
   *   Embedded media and content JSON-LD data from a text value.
   */
  protected function getEntitiesData($value) {
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $types = [];
    foreach ($xpath->query($this->xpath) as $dom_node) {
      /** @var \DOMElement $dom_node */
      $embed_entity_type_id = $dom_node->getAttribute('data-entity-type');
      $embed_uuid = $dom_node->getAttribute('data-entity-uuid');
      $embed_data = $this->getEntityData($embed_entity_type_id, $embed_uuid);
      if ($embed_data) {
        $types["schemadotorg_jsonld_embed-$embed_entity_type_id-$embed_uuid"] = $embed_data;
      }
    }
    return $types;
  }

  /**
   * Get embedded media and content JSON-LD data.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $uuid
   *   The entity uuid.
   *
   * @return array|null
   *   Embedded media and content JSON-LD data.
   */
  protected function getEntityData($entity_type_id, $uuid) {
    $embed_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $embed_entities = $embed_storage->loadByProperties(['uuid' => $uuid]);
    if (!$embed_entities) {
      return NULL;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $embed_entity */
    $embed_entity = reset($embed_entities);
    if (!$embed_entity->accesss()) {
      return NULL;
    }

    return $this->schemaJsonLdBuilder->buildEntity($embed_entity);
  }

}
