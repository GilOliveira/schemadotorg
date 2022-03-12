<?php

namespace Drupal\schemadotorg_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Schema.org mapping form.
 *
 * @property \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity
 */
class SchemaDotOrgUiMappingForm extends EntityForm {

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
