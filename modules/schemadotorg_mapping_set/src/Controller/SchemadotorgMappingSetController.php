<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_mapping_set\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org Blueprints Mapping Sets routes.
 */
class SchemadotorgMappingSetController extends ControllerBase {

  /**
   * The Schema.org mapping manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $schemaMappingManager;

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
    $instance->schemaMappingManager = $container->get('schemadotorg.mapping_manager');
    $instance->schemaMappingSetManager = $container->get('schemadotorg_mapping_set.manager');
    return $instance;
  }

  /**
   * Builds the response for the mapping sets overview page.
   */
  public function overview(): array {
    // Header.
    $header = [
      'title' => ['data' => $this->t('Title'), 'width' => '15%'],
      'name' => ['data' => $this->t('Name'), 'width' => '15%'],
      'setup' => ['data' => $this->t('Setup'), 'width' => '10%'],
      'types' => ['data' => $this->t('Types'), 'width' => '50%'],
      'operations' => ['data' => $this->t('Operations'), 'width' => '10%'],
    ];

    // Track if the devel_generate.module is enabled.
    $devel_generate_exists = $this->moduleHandler()->moduleExists('devel_generate');

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
        if ($devel_generate_exists) {
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
      $operations['view'] = [
        'title' => $this->t('View details'),
        'url' => Url::fromRoute(
          'schemadotorg_mapping_set.details',
          ['name' => $name],
        ),
      ];

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
      $row['title'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $mapping_set['label'],
          '#url' => Url::fromRoute('schemadotorg_mapping_set.details', ['name' => $name]),
        ],
      ];
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

  /**
   * Builds the response for the mapping set detail page.
   */
  public function details(string $name, bool $open = TRUE): array {
    $mapping_set = $this->config('schemadotorg_mapping_set.settings')->get("sets.$name");
    if (empty($mapping_set)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping');

    $build = [];
    $build['#title'] = $this->t('@label Schema.org mapping set', ['@label' => $mapping_set['label']]);

    $types = $mapping_set['types'];
    foreach ($types as $type) {
      if (!$this->schemaMappingSetManager->isValidType($type)) {
        continue;
      }
      [$entity_type_id, $schema_type] = explode(':', $type);

      $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
      $mapping_defaults = $this->schemaMappingManager->getMappingDefaults($entity_type_id, NULL, $schema_type);

      $t_args = [
        '@label' => $mapping_defaults['entity']['label'],
        '@type' => $type,
      ];
      $build[$type] = [
        '#type' => 'details',
        '#title' => $this->t('@label (@type)', $t_args),
        '#open' => $open,
      ];

      // Entity.
      $build[$type]['schema_type'] = [
        '#type' => 'item',
        '#title' => $this->t('Schema.org type'),
        '#markup' => $schema_type,
      ];
      if ($mapping) {
        $entity_type = $mapping->getTargetEntityBundleEntity()
          ->toLink($type, 'edit-form')
          ->toRenderable();
      }
      else {
        $entity_type = ['#markup' => $entity_type_id . ':' . $mapping_defaults['entity']['id']];
      }
      $build[$type]['entity_type'] = [
        '#type' => 'item',
        '#title' => $this->t('Entity type and bundle'),
        'item' => $entity_type,
      ];
      $build[$type]['label'] = [
        '#type' => 'item',
        '#title' => $this->t('Entity label'),
        '#markup' => $mapping_defaults['entity']['label'],
      ];

      $build[$type]['entity_description'] = [
        '#type' => 'item',
        '#title' => $this->t('Entity description'),
        '#markup' => $mapping_defaults['entity']['description'],
      ];

      // Properties.
      $rows = [];
      $field_prefix = $this->config('schemadotorg.settings')->get('field_prefix');
      foreach ($mapping_defaults['properties'] as $property_name => $property_definition) {
        if (empty($property_definition['name'])) {
          continue;
        }
        $row = [];
        $row['label'] = [
          'data' => [
            'name' => [
              '#markup' => $property_definition['label'],
              '#prefix' => '<strong>',
              '#suffix' => '</strong></br>',
            ],
            'description' => ['#markup' => $property_definition['description']],
          ],
        ];
        $row['property'] = $property_name;
        $row['arrow'] = '→';
        if ($property_definition['name'] === SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD) {
          $row['name'] = $field_prefix . '_' . $property_definition['machine_name'];
          $row['existing'] = $this->t('No');
        }
        else {
          $row['name'] = $property_definition['name'];
          $row['existing'] = $this->t('Yes');
        }
        $row['type'] = $property_definition['type'];
        $row['unlimited'] = !empty($property_definition['unlimited']) ? $this->t('Yes') : $this->t('No');
        $row['required'] = !empty($property_definition['required']) ? $this->t('Yes') : $this->t('No');
        $rows[] = $row;
      }
      $build[$type]['properties'] = [
        '#type' => 'table',
        '#header' => [
          'label' => ['data' => $this->t('Label / Description'), 'width' => '35%'],
          'property' => ['data' => $this->t('Schema.org property'), 'width' => '15%'],
          'arrow' => ['data' => '', 'width' => '1%'],
          'name' => ['data' => $this->t('Field name'), 'width' => '15%'],
          'existing' => ['data' => $this->t('Existing field'), 'width' => '10%'],
          'type' => ['data' => $this->t('Field type'), 'width' => '15%'],
          'unlimited' => ['data' => $this->t('Unlimited values'), 'width' => '5%'],
          'required' => ['data' => $this->t('Required field'), 'width' => '5%'],
        ],
        '#rows' => $rows,
      ];
    }

    return $build;
  }

}
