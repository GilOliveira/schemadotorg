<?php

namespace Drupal\schemadotorg_settings_element_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Provides a Scheme.org Blueprint Settings Element test form.
 */
class SchemaDotOrgSettingsElementTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schemadotorg_settings_element_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['settings'] = [
      '#tree' => TRUE,
    ];
    // Indexed.
    $form['settings'][SchemaDotOrgSettings::INDEXED] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::INDEXED,
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#default_value' => [
        'one',
        'two',
        'three',
      ],
    ];
    // Indexed Grouped.
    $form['settings'][SchemaDotOrgSettings::INDEXED_GROUPED] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#default_value' => [
        'A' => [
          'one',
          'two',
          'three',
        ],
        'B' => [
          'four',
          'five',
          'six',
        ],
      ],
    ];
    // Indexed Grouped Named.
    $form['settings'][SchemaDotOrgSettings::INDEXED_GROUPED_NAMED] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#default_value' => [
        'A' => [
          'name' => 'Group A',
          'items' => [
            'one',
            'two',
            'three',
          ],
        ],
        'B' => [
          'name' => 'Group B',
          'items' => [
            'four',
            'five',
            'six',
          ],
        ],
      ],
    ];
    // Associative.
    $form['settings'][SchemaDotOrgSettings::ASSOCIATIVE] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#associative' => TRUE,
      '#default_value' => [
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
      ],
    ];
    // Associative Grouped.
    $form['settings'][SchemaDotOrgSettings::ASSOCIATIVE_GROUPED] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED,
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED,
      '#default_value' => [
        'A' => [
          'one' => 'One',
          'two' => 'Two',
          'three' => 'Three',
        ],
        'B' => [
          'four' => 'Four',
          'five' => 'Five',
          'six' => 'Six',
        ],
      ],
    ];
    // Associative Grouped Names.
    $form['settings'][SchemaDotOrgSettings::ASSOCIATIVE_GROUPED_NAMED] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED_NAMED,
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED_NAMED,
      '#default_value' => [
        'A' => [
          'name' => 'Group A',
          'items' => [
            'one' => 'One',
            'two' => 'Two',
            'three' => 'Three',
          ],
        ],
        'B' => [
          'name' => 'Group B',
          'items' => [
            'four' => 'Four',
            'five' => 'Five',
            'six' => 'Six',
          ],
        ],
      ],
    ];
    // Links.
    $form['settings'][SchemaDotOrgSettings::LINKS] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::LINKS,
      '#settings_type' => SchemaDotOrgSettings::LINKS,
      '#default_value' => [
        ['uri' => 'https://yahoo.com', 'title' => 'Yahoo!!!'],
        ['uri' => 'https://google.com', 'title' => 'Google'],
      ],
    ];
    // Links grouped.
    $form['settings'][SchemaDotOrgSettings::LINKS_GROUPED] = [
      '#type' => 'schemadotorg_settings',
      '#title' => SchemaDotOrgSettings::LINKS_GROUPED,
      '#settings_type' => SchemaDotOrgSettings::LINKS_GROUPED,
      '#default_value' => [
        'A' => [
          ['uri' => 'https://yahoo.com', 'title' => 'Yahoo!!!'],
        ],
        'B' => [
          ['uri' => 'https://google.com', 'title' => 'Google'],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('settings');
    $this->messenger()->addStatus(Markup::create('<pre>' . Yaml::encode($settings) . '</pre>'));
  }

}
