Schema.org Blueprints
---------------------

# Todo

UI/UX
- Use Ajax for the add field form.
  - Use Thing for testing
  - Examples
    - \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm::buildSchemaPropertiesForm
    - \Drupal\form_api_example\Form\AjaxAddMore
    - Drupal.behaviors.fileAutoUpload
  - Steps
    - Pass $form_state to properties
    - Validate and massage properties
    - SchemaDotOrgUiField element
      - name, type, description, unlimited, etc...

- Improve mapping type listing page, reduce the number of columns.

- Document recommended modules
  - Address - https://www.drupal.org/project/address
  - Time field - https://www.drupal.org/project/time_field
  - Field group - https://www.drupal.org/project/field_group

- TDB
  - Flex field - https://www.drupal.org/project/flexfield
  - Key value field - https://www.drupal.org/project/key_value_field
  - Duration Field - https://www.drupal.org/project/duration_field

Ongoing
- Determine the recommended types per entity type.
- Build out the default schema types properties.
- Review patterns and tests.

Documentation
- Add hook_help() to Schema.org Structure and Reports.
- Add details usage to #description to admin settings.

Code
- Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
  to support subtype.

Research
- https://www.lullabot.com/articles/write-better-code-typed-entity

# Testing

- If entity reference exists recommend it as field type otherwise recommend plain text.
- \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface::getRangeIncludesTargetBundles
- Improve \Drupal\Tests\schemadotorg_ui\Kernel\SchemaDotOrgUiApiTest
- Create SchemaDotOrgUiMappingForm kernel test.
  - Check all functionality.
- JavaScript test coverage for UI and Report.
- FormValidation test coverage for SchemaDotOrgFormTrait

# Best Practices

- If two properties can address similar use-cases, use the more common property.
  - For example, Place can have an 'image' and 'photo'.
    It is simpler to use 'image'.

- For high-level types, which are inherited from, we want to keep the
  default properties as simple as possible.

- For specific and important types, include Recipe, we should be specific
  as needed with the default properties.

# TBD

- Should you be able to map the same field to multiple properties?
  - body => description and disambiguatingDescription

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?

```
      $row['field']['add_field'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add field'),
        '#submit' => ['::addField'],
        '#validate' => ['::noValidate'],
//        '#ajax' => [
//          'callback' => '::addFieldCallback',
//          'wrapper' => $wrapper_id,
//        ],
      ];

  /**
   * No validate handler for the "add-field" button.
   */
  public function noValidate(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * Submit handler for the "add-field" button.
   */
  public function addField(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Callback for the "add-field" button.
   */
  public function addFieldCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element[static::ADD_FIELD];
  }
```
