<?php

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Schema.org entity type manager.
 */
class SchemaDotOrgEntityTypeManager implements SchemaDotOrgEntityTypeManagerInterface {
  use StringTranslationTrait;

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
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->schemaTypeManager = $schema_type_manager;
  }

  /**
   * {@inheritdoc}
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

  /**
   * Get default Schema.org type for an entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string|null
   *   The default Schema.org type for an entity type and bundle.
   */
  public function getDefaultSchemaType($entity_type_id, $bundle) {
    $default_schema_types = [
      'user.user' => 'Person',
      'media.audio' => 'AudioObject',
      'media.image' => 'ImageObject',
      'media.remote_video' => 'VideoObject',
      'media.video' => 'VideoObject',
      'media.document' => 'DataDownload',
    ];
    return $default_schema_types[$entity_type_id . '.' . $bundle] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldNames($entity_type_id) {
    $base_fields_names = [
      'node' => [
        'uuid',
        'revision_uid',
        'uid',
        'title',
        'created',
        'changed',
        'promote',
        'sticky',
        'path',
      ],
      'user' => [
        'uuid',
        'name',
        'mail',
      ],
    ];
    return $base_fields_names[$entity_type_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCommonSchemaTypes($entity_type_id) {
    $common_types = [];
    $common_types['node'] = [
      (string) $this->t('Common') => ['Thing', 'Person', 'Place', 'Event'],
      (string) $this->t('Content') => ['CreativeWork', 'Article', 'NewsArticle', 'Blog', 'Book', 'FAQ', 'Recipe', 'WebPage'],
      (string) $this->t('Education') => ['Course', 'CollegeOrUniversity', 'ElementarySchool', 'HighSchool'],
      (string) $this->t('Entertainment') => ['Movie', 'MusicRecording', 'TVSeries', 'VideoGame'],
      (string) $this->t('Health') => ['Physician', 'Patient', 'Drug', 'MedicalCondition', 'MedicalGuideline', 'MedicalWebPage', 'MedicalScholarlyArticle', 'ResearchProject'],
      (string) $this->t('Business') => ['Organization', 'LocalBusiness', 'Corporation', 'Restaurant', 'NGO'],
      (string) $this->t('Commerce') => ['Product', 'Offer', 'Review'],
    ];
    $common_types['media'] = [
      (string) $this->t('Media objects') => ['AudioObject', 'ImageObject', 'VideoObject', '3DModel', 'DataDownload'],
    ];
    $common_types['paragraph'] = [
      (string) $this->t('Common') => ['Thing', 'ContactPoint'],
      (string) $this->t('Content') => ['DefinedTerm', 'ItemList'],
      (string) $this->t('Values') => ['PropertyValue', 'QuantitativeValue'],
      (string) $this->t('Business') => ['Audience', 'Brand', 'Invoice', 'JobPosting', 'OwnershipInfo', 'OpeningHoursSpecification', 'Occupation', 'VirtualLocation'],
      (string) $this->t('Commerce') => ['Offer', 'Order', 'Rating', 'Service', 'Ticket', 'Trip', 'MonetaryAmount', 'OfferShippingDetails', 'PriceSpecification'],
      (string) $this->t('Other') => ['HealthInsurancePlan', 'ComputerLanguage', 'NutritionInformation'],
    ];
    $common_types['block_content'] = $common_types['paragraph'];
    return $common_types[$entity_type_id] ?? $common_types['node'];
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

}
