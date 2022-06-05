<?php

namespace Drupal\schemadotorg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org relationships routes.
 */
class SchemaDotOrgRelationshipsController extends ControllerBase {

  /**
   * Builds the Schema.org relationships table.
   *
   * @return array
   *   A renderable array containing a Schema.org relationships table.
   */
  public function index() {
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
      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No Schema.org relationships found.'),
      '#sticky' => TRUE,
    ];
  }

}
