<?php

namespace Drupal\schemadotorg_taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Schema.org taxonomy manager.
 */
class SchemaDotOrgTaxonomyManager implements SchemaDotOrgTaxonomyManagerInterface {

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
   * Constructs a SchemaDotOrgTaxonomyManager object.
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
  public function load(array &$data, EntityInterface $entity) {
    if (!$entity instanceof VocabularyInterface) {
      return;
    }

    // Alter a vocabulary's Schema.org type data to use DefinedTermSet @type.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
    $mappings = $this->getMappingStorage()->loadByProperties([
      'target_entity_type_id' => 'taxonomy_term',
      'target_bundle' => $entity->id(),
    ]);
    if (!$mappings) {
      return;
    }

    $mapping = reset($mappings);
    $schema_type = $mapping->getSchemaType();
    $data['@type'] = "{$schema_type}Set";
    $data['name'] = $entity->label();
    if ($entity->getDescription()) {
      $data['description'] = $entity->getDescription();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(array &$data, EntityInterface $entity) {
    if (!$entity instanceof TermInterface) {
      return;
    }

    // Alter a term's Schema.org type data to include isDefinedTermSet property.
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if (!$mapping) {
      return;
    }

    // Check that the term is mapping to a DefinedTerm or CategoryCode.
    $schema_type = $mapping->getSchemaType();
    $is_defined_term = in_array($schema_type, ['DefinedTerm', 'CategoryCode']);
    if (!$is_defined_term) {
      return;
    }

    // Append isDefinedTermSet or isCategoryCodeSet data to the type data.
    $vocabulary = $entity->get('vid')->entity;
    $vocabulary_data = $this->schemaJsonLdBuilder->buildEntity($vocabulary);
    $data["in{$schema_type}Set"] = $vocabulary_data;
  }

  /**
   * Gets Schema.org mapping storage.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface
   *   The Schema.org mapping storage.
   */
  protected function getMappingStorage() {
    return $this->entityTypeManager->getStorage('schemadotorg_mapping');
  }

}
