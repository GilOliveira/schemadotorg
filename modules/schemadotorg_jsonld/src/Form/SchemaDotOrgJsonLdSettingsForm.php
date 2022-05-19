<?php

namespace Drupal\schemadotorg_jsonld\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org JSON-LD settings for this site.
 */
class SchemaDotOrgJsonLdSettingsForm extends ConfigFormBase {

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_jsonld_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_jsonld.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routerBuilder = $container->get('router.builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('schemadotorg_jsonld.settings');

    $form['property_order'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'properthName',
      '#title' => $this->t('Schema.org property order'),
      '#description' => $this->t('Enter the default Schema.org property order.'),
      '#default_value' => $config->get('property_order'),
    ];
    $form['property_image_styles'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'propertyName|image_style',
      '#title' => $this->t('Schema.org property image styles'),
      '#description' => $this->t('Enter the Schema.org property and the desired image style.'),
      '#default_value' => $config->get('property_image_styles'),
    ];
    $form['entity_type_resource_paths'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'entity_type_id|path',
      '#title' => $this->t('Entity type resource paths'),
      '#description' => $this->t('Enter the entity type and the desired resource path.'),
      '#default_value' => $config->get('entity_type_resource_paths'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->routerBuilder->setRebuildNeeded();
    $this->config('schemadotorg_jsonld.settings')
      ->set('property_order', $form_state->getValue('property_order'))
      ->set('property_image_styles', $form_state->getValue('property_image_styles'))
      ->set('entity_type_resource_paths', $form_state->getValue('entity_type_resource_paths'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
