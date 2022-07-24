<?php

namespace Drupal\schemadotorg_mapping_set\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org Blueprints Mapping Sets routes.
 */
class SchemadotorgMappingSetController extends ControllerBase {

  /**
   * The Schema.org mapping set manager service.
   *
   * @var \Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface
   */
  protected $schemaMappingSetManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->schemaMappingSetManager = $container->get('schemadotorg_mapping_set.manager');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function build() {
    // Header.
    $header = [
      'title' => ['data' => $this->t('Title'), 'width' => '15%'],
      'name' => ['data' => $this->t('Name'), 'width' => '15%'],
      'setup' => ['data' => $this->t('Setup'), 'width' => '10%'],
      'types' => ['data' => $this->t('Types'), 'width' => '50%'],
      'operations' => ['data' => $this->t('Operations'), 'width' => '10%'],
    ];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping');

    // Rows.
    $rows = [];
    $mapping_sets = $this->config('schemadotorg_mapping_set.settings')->get('sets');
    foreach ($mapping_sets as $name => $mapping_set) {
      $is_setup = $this->schemaMappingSetManager->isSetup($name);

      // Operations.
      $operations = [];
      if (!$is_setup) {
        $operations['setup'] = $this->t('Setup types');
      }
      else {
        if ($this->moduleHandler()->moduleExists('devel_generate')) {
          $operations['generate'] = $this->t('Generate content');
          $operations['kill'] = $this->t('Kill content');
        }
        $operations['teardown'] = $this->t('Teardown types');
      }
      foreach ($operations as $operation => $title) {
        $operations[$operation] = [
          'title' => $title,
          'url' => Url::fromRoute(
            'schemadotorg_mapping_set.confirm_form',
            ['name' => $name, 'operation' => $operation],
          ),
        ];
      }

      // Types.
      $invalid_types = [];
      $types = $mapping_set['types'];
      foreach ($types as $index => $type) {
        if ($this->schemaMappingSetManager->isValidType($type)) {
          [$entity_type_id, $schema_type] = explode(':', $type);
          $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
          if ($mapping) {
            $entity_type_bundle = $mapping->getTargetEntityBundleEntity();
            $types[$index] = $entity_type_bundle->toLink($type, 'edit-form')->toString();
          }
        }
        else {
          $invalid_types[] = $type;
          $types[$index] = '<strong>' . $type . '</strong>';
        }
      }

      $row = [];
      $row['title'] = $mapping_set['label'];
      $row['name'] = $name;
      $row['setup'] = $is_setup ? $this->t('Yes') : $this->t('No');
      $row['types'] = ['data' => ['#markup' => implode(', ', $types)]];
      // Only show operation when there are no invalid types.
      if (!$invalid_types) {
        $row['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $operations,
          ],
        ];
      }
      else {
        $row['operations'] = '';
      }

      if ($invalid_types) {
        $rows[] = ['data' => $row, 'class' => ['color-error']];
      }
      elseif ($is_setup) {
        $rows[] = ['data' => $row, 'class' => ['color-success']];
      }
      else {
        $rows[] = $row;
      }

      // Display error message able invalid types.
      if ($invalid_types) {
        $t_args = [
          '%set' => $mapping_set['label'],
          '%types' => implode(', ', $invalid_types),
          ':href' => Url::fromRoute('schemadotorg_mapping_set.settings')->toString(),
        ];
        $message = $this->t('%types in %set are not valid. <a href=":href">Please update this information.</a>', $t_args);
        $this->messenger()->addError($message);
      }
    }

    return [
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ],
    ];
  }

}
