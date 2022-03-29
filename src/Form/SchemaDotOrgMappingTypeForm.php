<?php

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schema.org mapping type form.
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $entity
 */
class SchemaDotOrgMappingTypeForm extends EntityForm
{
  use SchemaDotOrgFormTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $entity */
    $entity = $this->getEntity();

    if ($entity->isNew()) {
      $form['targetEntityType'] = [
        '#type' => 'select',
        '#title' => $this->t('Target entity type'),
        '#options' => $this->getTargetEntityTypeOptions(),
        '#required' => TRUE,
      ];
    }
    else {
      $form['target_entity_type'] = [
        '#type' => 'item',
        '#title' => $this->t('Target entity type'),
        '#value' => $entity->id(),
        '#markup' => $entity->label(),
      ];
    }
    $form['default_schema_types'] = [
      '#type' => 'textarea',
      '#title' => 'Default Schema.org types',
      '#description' => $this->t('Enter one value per line, in the format entity_type|schema_type.'),
      '#default_value' => $this->keyValuesString($entity->get('default_schema_types')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['default_schema_properties'] = [
      '#type' => 'textarea',
      '#title' => 'Default Schema.org properties',
      '#description' => $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($entity->get('default_schema_properties')),
      '#element_validate' => ['::validateList'],
    ];
    $form['default_base_fields'] = [
      '#type' => 'textarea',
      '#title' => 'Default base field mappings',
      '#description' => $this->t('Enter one value per line, in the format base_field_name|property_name.')
      . '<br/>' . $this->t('The property_name value be left blank if you want the base field available but not mapped to a Schema.org property.'),
      '#default_value' => $this->keyValuesString($entity->get('default_base_fields')),
      '#element_validate' => ['::validateKeyValues'],
    ];
    $form['default_unlimited_fields'] = [
      '#type' => 'textarea',
      '#title' => 'Default unlimited Schema.org properties',
      '#description' => $this->t('Enter one Schema.org property per line.'),
      '#default_value' => $this->listString($entity->get('default_unlimited_fields')),
      '#element_validate' => ['::validateList'],
    ];
    $form['recommended_schema_types'] = [
      '#type' => 'textarea',
      '#title' => 'Recommended Schema.org types',
      '#description' => $this->t('Enter one value per line, in the format group_name|group_label|SchemaType01,SchemaType01,SchemaType01.'),
      '#default_value' => $this->groupedListString($entity->get('recommended_schema_types')),
      '#element_validate' => ['::validateGroupedList'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $t_args = ['%label' => $this->getEntity()->label()];
    $message = ($result === SAVED_NEW)
      ? $this->t('Created %label mapping type.', $t_args)
      : $this->t('Updated %label mapping type.', $t_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

  /* ************************************************************************ */
  // Options.
  /* ************************************************************************ */

  /**
   * Get available target content entity type options.
   *
   * @return array
   *   Available target content entity type options.
   */
  protected function getTargetEntityTypeOptions() {
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    $definitions = $this->entityTypeManager->getDefinitions();

    $options = [];
    foreach ($definitions as $definition) {
      if ($definition instanceof ContentEntityTypeInterface
        && !$mapping_type_storage->load($definition->id())) {
        $options[$definition->id()] = $definition->getLabel();
      }
    }
    return $options;
  }

}
