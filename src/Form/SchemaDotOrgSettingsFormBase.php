<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for configuring Schema.org Blueprints settings.
 */
abstract class SchemaDotOrgSettingsFormBase extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#after_build'][] = [get_class($this), 'afterBuildDetails'];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form #after_build callback: Track details element's open/close state.
   */
  public static function afterBuildDetails(array $form, FormStateInterface $form_state): array {
    $form_id = $form_state->getFormObject()->getFormId();

    // Only open the first details element.
    $is_first = TRUE;
    foreach (Element::children($form) as $child_key) {
      if (NestedArray::getValue($form, [$child_key, '#type']) === 'details') {
        $form[$child_key]['#open'] = $is_first;
        $is_first = FALSE;

        $form[$child_key]['#attributes']['data-schemadotorg-details-key'] = "details-$form_id-$child_key";
      }
    }
    $form['#attached']['library'][] = 'schemadotorg/schemadotorg.details';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Update configuration for schemadotorg_* sub-modules.
    foreach (Element::children($form) as $element_key) {
      if (str_starts_with($element_key, 'schemadotorg_')
        && $this->moduleHandler->moduleExists($element_key)
        && !$this->configFactory()->get($element_key . '.settings')->isNew()) {
        $config = $this->configFactory()->getEditable($element_key . '.settings');
        $values = $form_state->getValue($element_key);
        foreach ($values as $key => $value) {
          $config->set($key, $value);
        }
        $config->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

}
