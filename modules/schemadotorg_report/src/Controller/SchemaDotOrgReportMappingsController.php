<?php

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Link;
use Drupal\field_ui\FieldUI;

/**
 * Returns responses for Schema.org report mapping routes.
 */
class SchemaDotOrgReportMappingsController extends SchemaDotOrgReportControllerBase {

  /**
   * Builds the Schema.org mapping recommendations.
   *
   * @return array
   *   A renderable array containing the Schema.org mapping recommendations.
   */
  public function recommendations() {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping_type');

    $header = [
      ['data' => $this->t('Entity type'), 'width' => '10%'],
      ['data' => $this->t('Group'), 'width' => '10%'],
      ['data' => $this->t('Schema.org breadcrumb'), 'width' => '30%'],
      ['data' => $this->t('Schema.org type'), 'width' => '10%'],
      ['data' => $this->t('Schema.org properties'), 'width' => '40%'],
    ];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface[] $mapping_types */
    $mapping_types = $mapping_type_storage->loadMultiple();
    $rows = [];
    foreach ($mapping_types as $mapping_type) {
      $recomended_types = $mapping_type->getRecommendedSchemaTypes();
      foreach ($recomended_types as $recommendation_type) {
        foreach ($recommendation_type['types'] as $type) {
          // Display message when a recommended type does not exist.
          if (!$this->schemaTypeManager->isType($type)) {
            $t_args = [
              '@entity' => $mapping_type->id(),
              '%type' => $type,
            ];
            $message = $this->t('Schema.org type %type does not exists. Please update the @entity recommended types.', $t_args);
            $this->messenger()->addWarning($message);
            continue;
          }

          $properties = $mapping_type->getDefaultSchemaTypeProperties($type);

          $row = [];

          $row[] = $mapping_type->label();
          $row[] = $recommendation_type['label'];
          $row[] = ['data' => $this->buildTypeBreadcrumbs($type)];
          $row[] = ['data' => $this->schemaTypeBuilder->buildItemsLinks($type)];
          $row[] = $properties ? ['data' => $this->schemaTypeBuilder->buildItemsLinks($properties)] : '';
          if (empty($properties)) {
            $rows[] = ['data' => $row, 'class' => ['color-warning']];
          }
          else {
            $rows[] = $row;
          }
        }
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no Schema.org recommendations yet.'),
      '#sticky' => TRUE,
    ];
  }

  /**
   * Builds the Schema.org mapping relationships.
   *
   * @return array
   *   A renderable array containing the Schema.org mapping relationships.
   */
  public function relationships() {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping');

    /** @var \Drupal\field\FieldConfigStorage $field_storage */
    $field_storage = $this->entityTypeManager()->getStorage('field_config');

    $header = [
      $this->t('Entity type'),
      $this->t('Bundle'),
      $this->t('Field name'),
      $this->t('Property'),
      $this->t('Range includes'),
      $this->t('Target type'),
      $this->t('Expected targets'),
      $this->t('Actual targets'),
      $this->t('Operation'),
    ];

    $entity_ids = $field_storage->getQuery()
      ->condition('field_type', ['entity_reference', 'entity_reference_revisions'], 'IN')
      ->sort('id')
      ->execute();
    /** @var \Drupal\Core\Field\FieldConfigInterface[] $fields */
    $fields = $field_storage->loadMultiple($entity_ids);
    $rows = [];
    foreach ($fields as $field) {
      $field_name = $field->getName();
      $entity_type_id = $field->getTargetEntityTypeId();
      $bundle = $field->getTargetBundle();
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
      $mapping = $mapping_storage->load("$entity_type_id.$bundle");
      if (!$mapping) {
        continue;
      }

      $schema_type = $mapping->getSchemaType();
      $schema_property = $mapping->getSchemaPropertyMapping($field_name);
      if (!$schema_property) {
        continue;
      }

      // Get range includes.
      $range_includes = $mapping_storage->getSchemaPropertyRangeIncludes($schema_type, $schema_property);

      // Get expected target bundles.
      $target_type = $field->getSetting('target_type');
      $expected_target_bundles = $mapping_storage->getSchemaPropertyTargetBundles($target_type, $schema_type, $schema_property);

      // Get actual target bundles.
      $handler_settings = $field->getSetting('handler_settings');
      $actual_target_bundles = $handler_settings['target_bundles'];

      // Manage link.
      if ($route_info = FieldUI::getOverviewRouteInfo($entity_type_id, $bundle)) {
        $link = Link::fromTextAndUrl($this->t('Manage'), $route_info)->toRenderable()
          + ['#attributes' => ['class' => ['button', 'button--small']]];
      }
      else {
        $link = [];
      }

      $row = [];
      $row[] = $entity_type_id;
      $row[] = $bundle;
      $row[] = $field_name;
      $row[] = $schema_property;
      $row[] = implode('; ', $range_includes);
      $row[] = $target_type;
      $row[] = implode('; ', $expected_target_bundles);
      $row[] = implode('; ', $actual_target_bundles);
      $row[] = ['data' => $link];
      if ($expected_target_bundles != $actual_target_bundles) {
        $rows[] = ['data' => $row, 'class' => ['color-warning']];
      }
      else {
        $rows[] = $row;
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no Schema.org relationships yet.'),
      '#sticky' => TRUE,
    ];
  }

}