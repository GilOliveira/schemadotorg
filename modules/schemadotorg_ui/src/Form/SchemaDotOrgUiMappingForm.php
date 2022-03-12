<?php

namespace Drupal\schemadotorg_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schema.org mapping form.
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity
 *
 * @see \Drupal\field_ui\Form\EntityDisplayFormBase
 */
class SchemaDotOrgUiMappingForm extends EntityForm {

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
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * The Schema.org entity type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager
   */
  protected $schemaEntityTypeManager;

  /**
   * The Schema.org entity type builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder
   */
  protected $schemaEntityTypeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    $instance->schemaEntityTypeManager = $container->get('schemadotorg.entity_type_manager');
    $instance->schemaEntityTypeBuilder = $container->get('schemadotorg.entity_type_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $route_parameters = $route_match->getParameters()->all();

    $entity_type_id = $route_parameters['entity_type_id'];
    $bundle = $route_parameters['bundle'];
    $type = $this->getRequest()->query->get('type');

    $storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    return $storage->load($entity_type_id . '.' . $bundle)
      ?: $storage->create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'type' => $type,
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['targetEntityType'] = [
      '#type' => 'textfield',
      '#title' => $this->t('targetEntityType'),
      '#default_value' => $this->entity->get('targetEntityType'),
      '#required' => TRUE,
    ];
    $form['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bundle'),
      '#default_value' => $this->entity->get('bundle'),
      '#required' => TRUE,
    ];
    $form['type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('type'),
      '#default_value' => $this->entity->get('type'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new Schema.org mapping %label.', $message_args)
      : $this->t('Updated Schema.org mapping %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
