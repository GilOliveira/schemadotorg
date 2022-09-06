<?php

namespace Drupal\schemadotorg_next_components;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Schema.org Next.js components builder.
 */
class SchemaDotOrgNextComponentsBuilder implements SchemaDotOrgNextComponentsBuilderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The JSON:API Resource Type Repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * Constructs a SchemaDotOrgNextComponentBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The entity display repository.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $display_repository,
    ResourceTypeRepositoryInterface $resource_type_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $display_repository;
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity($entity_type_id) {
    $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType();
    $bundle_entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type);

    $bundles = $bundle_entity_storage->loadMultiple();

    $resource_types = [];
    foreach ($bundles as $bundle) {
      $resource_type = 'node--' . $bundle->id();
      $component_name = ucfirst($entity_type_id) . ucfirst($bundle->id());
      $resource_types[$resource_type] = $component_name;
    }

    $next_imports = [];
    $next_switch_cases = [];
    foreach ($resource_types as $resource_type => $component_name) {
      $next_imports[] = "import { $component_name } from 'components/$resource_type';";
      $next_switch_cases[] = "case '$resource_type': return <$component_name node={resource} />;";
    }
    $next_imports[] = "import { DrupalEntity } from 'components/entity';";
    $next_switch_cases[] = " default: return  <DrupalEntity entity={resource} />;";

    $imports = implode(PHP_EOL, $next_imports);
    $switch_cases = implode(PHP_EOL . PHP_EOL, $next_switch_cases);
    $resource_types = Json::encode(array_keys($resource_types));
    return <<<EOT
        import * as React from "react";
        import { DrupalNode } from "next-drupal";

        $imports

        export const RESOURCE_TYPES = $resource_types;

        interface NodePageProps {
          resource: DrupalNode;
        }

        export function Node({ resource }: NodePageProps) {
          switch (resource.type) {
            $switch_cases
          }
        }
      EOT;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntityBundle($entity_type_id, $bundle) {
    $base_name = 'Drupal' . ucfirst($entity_type_id);
    $component_name = ucfirst($entity_type_id) . ucfirst($bundle);
    $props_name = ucfirst($entity_type_id) . ucfirst($bundle) . 'Props';

    $view_display = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle);

    $next_imports = [
      "import {" . $base_name . "} from 'next-drupal'",
    ];

    // Components.
    $display_components = $view_display->getComponents();
    unset($display_components['title']);
    foreach ($display_components as $field_name => $display_component) {
      $field_component = $this->buildNextFieldComponent($entity_type_id, $bundle, $field_name, $display_component, $next_imports);
      if ($field_component) {
        $display_components[$field_name]['next'] = $field_component;
      }
      else {
        unset($display_components[$field_name]);
      }
    }

    // Field groups.
    $field_groups = $view_display->getThirdPartySettings('field_group');
    foreach ($field_groups as $group_name => $field_group) {
      $children_field_names = array_combine($field_group['children'], $field_group['children']);
      $children = array_intersect_key($display_components, $children_field_names);
      if ($children) {
        $field_groups[$group_name]['next'] = $this->buildNextGroupComponent($field_group, $children);
      }
      else {
        unset($field_groups[$group_name]);
      }
      $display_components = array_diff_key($display_components, $children_field_names);
    }

    // Get all Next.js components sorted by weight.
    // phpcs:disable
//    $all_components = $field_groups + $display_components;
//    uasort($all_components, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    // phpcs:enable

    uasort($field_groups, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    uasort($display_components, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $all_components = $field_groups + $display_components;
    $next_components = array_map(function ($component) {
      return $component['next'];
    }, $all_components);

    $imports = implode(PHP_EOL, array_unique($next_imports));
    $components = implode(PHP_EOL . PHP_EOL, $next_components);

    return <<<EOT
      $imports

      interface $props_name {
        node: $base_name
      }

      export function $component_name({ node, ...props }: $props_name) {
        return (
          <article {...props}>

            <h1 className="mb-4 text-6xl">{node.title}</h1>

            $components

          </article>
        )
      }
      EOT;
  }

  /**
   * Build a Next.js field group component.
   *
   * @param array $field_group
   *   The field group settings.
   * @param array $children
   *   The field group's children.
   *
   * @return string
   *   A Next.js field group component.
   */
  protected function buildNextGroupComponent(array $field_group, array $children) {
    $label = $field_group['label'];

    $next_components = array_map(function ($child) {
      return $child['next'];
    }, $children);
    $components = implode(PHP_EOL, $next_components);

    return <<<EOT
    <section>

      <h2 className="mb-2 text-4xl">$label</h2>

      $components

    </section>
    EOT;
  }

  /**
   * Build a Next.js field component.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param array $field_component
   *   The field display settings.
   * @param array $next_imports
   *   The Next.js import directives.
   *
   * @return string|null
   *   A Next.js field component.
   */
  protected function buildNextFieldComponent($entity_type_id, $bundle, $field_name, array $field_component, array &$next_imports) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $field_definition = $field_definitions[$field_name] ?? NULL;
    if (!$field_definition) {
      return NULL;
    }

    $public_name = $this->getPublicName($entity_type_id, $bundle, $field_name);

    $is_multiple = ($field_definition->getFieldStorageDefinition()->getCardinality() !== 1);

    $field_type = $field_definition->getType();
    $component_type = $this->getComponentType($field_type);
    switch ($component_type) {
      case 'text':
        $component_field = "$entity_type_id.$public_name?.processed";
        $component_value = $is_multiple
          ? "<div>{ $entity_type_id.$public_name.map((item, i) => <div key={i} dangerouslySetInnerHTML={{ __html: item.processed }} />) }</div>"
          : "<div dangerouslySetInnerHTML={{ __html: $entity_type_id.$public_name.processed }} />";
        break;

      case 'value';
        $component_field = "$entity_type_id.$public_name";
        $component_value = $is_multiple
         ? "<div>{ $entity_type_id.$public_name.map((value, i) => <div key={i}>{value}</div>) }</div>"
         : "<div>{ $entity_type_id.$public_name }</div>";
        break;

      case 'time':
      case 'datetime':
        $format_function = ($component_type === 'time') ? 'formatTime' : 'formatDateTime';

        $next_imports = array_merge(["import { $format_function } from 'lib/format-date';"], $next_imports);

        $component_field = "$entity_type_id.$public_name";
        $component_value = $is_multiple
          ? "<div>{ $entity_type_id.$public_name.map((value, i) => <div key={i}>{ $format_function(value) }</div>) }</div>"
          : "<div>{ $format_function($entity_type_id.$public_name) }</div>";
        break;

      case 'email';
      case 'telephone';
        $protocols = [
          'telephone' => 'tel:',
          'email' => 'mailto:',
        ];
        $protocol = $protocols[$component_type] ?? '';
        $component_field = "$entity_type_id.$public_name";
        if ($is_multiple) {
          $component_value = <<<EOT
            <div>
              { $entity_type_id.$public_name.map((value, i) => (
                <div key={i}>
                  <a className="hover:text-blue-600" href={'$protocol' + value}>{ value }</a>
                </div>
              ))}
            </div>
            EOT;
        }
        else {
          $component_value = <<<EOT
            <a className="hover:text-blue-600" href={'$protocol' + $entity_type_id.$public_name}>{ $entity_type_id.$public_name }</a>
            EOT;
        }
        break;

      case 'link';
        $component_field = "$entity_type_id.$public_name";
        if ($is_multiple) {
          $component_value = <<<EOT
            <div>
              { $entity_type_id.$public_name.map((item, i) => (
                <div key={i}>
                  <a className="hover:text-blue-600" href={item.uri}>{ item.title || item.uri }</a>
                </div>
              ))}
            </div>
            EOT;
        }
        else {
          $component_value = <<<EOT
            <a className="hover:text-blue-600" href={ $entity_type_id.$public_name.uri}>{ $entity_type_id.$public_name.title || $entity_type_id.$public_name.uri }</a>
            EOT;
        }
        break;

      case 'image':
      case 'entity_reference':
        if ($component_type === 'image') {
          $next_imports[] = "import { DrupalImage } from 'components/image'";
          $tag_name = 'DrupalImage';
          $attribute_name = 'image';
        }
        else {
          $tag_name = 'DrupalEntity';
          $attribute_name = 'entity';
          $next_imports[] = "import { DrupalEntity } from 'components/entity'";
        }
        $component_field = "$entity_type_id.$public_name";
        if ($is_multiple) {
          $component_value = <<<EOT
            <div>
              { $entity_type_id.$public_name.map((item, i) => (
                <$tag_name key={i} $attribute_name={ item } />
              ))}
            </div>
            EOT;
        }
        else {
          $component_value = <<<EOT
            <$tag_name $attribute_name={ $entity_type_id.$public_name } />
            EOT;
        }
        break;

      default:
        return NULL;
    }

    $field_label = $field_definition->getLabel();
    $component_label = '<h3 className="mb-1 text-2xl">' . $field_label . '</h3>';

    return <<<EOT
      { $component_field && (
        <div className="mb-4">
          $component_label
          $component_value
        </div>
      ) }
      EOT;
  }

  /**
   * Get the JSON:API public name for a field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The JSON:API public name for a field.
   */
  protected function getPublicName($entity_type_id, $bundle, $field_name) {
    return $this->resourceTypeRepository
      ->get($entity_type_id, $bundle)
      ->getPublicName($field_name);
  }

  /**
   * Get the component type for a field type.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return string
   *   The component type for a field type.
   */
  protected function getComponentType($field_type) {
    switch ($field_type) {
      case 'text_long':
      case 'text':
      case 'text_with_summary':
        return 'text';

      case 'list_string':
      case 'list_float':
      case 'list_integer':
      case 'decimal':
      case 'float':
      case 'integer':
      case 'string':
      case 'string_long':
      case 'boolean':
      case 'timestamp':
        return 'value';

      case 'address':
      case 'datetime':
      case 'file':
      case 'email':
      case 'entity_reference':
      case 'image':
      case 'link':
      case 'telephone':
      case 'time':
      case 'time_range':
      default:
        return $field_type;
    }
  }

}
