<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Base form for configuring Schema.org Blueprints settings.
 */
abstract class SchemaDotOrgSettingsFormBase extends ConfigFormBase {

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

}
