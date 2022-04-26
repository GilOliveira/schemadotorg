Schema.org Blueprints
---------------------

# Todo

UI/UX
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
