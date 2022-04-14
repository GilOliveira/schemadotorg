Schema.org Blueprints
---------------------

# Todo

TBD
- Sorting fields without field groups.

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

- Improve \Drupal\Tests\schemadotorg_ui\Kernel\SchemaDotOrgUiApiTest
- Create SchemaDotOrgUiMappingForm kernel test.
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
