Schema.org Blueprints
---------------------

# Todo

Top down exploration

Improve form validation
- Display warning when updating field types.

Improve \Drupal\Tests\schemadotorg_ui\Kernel\SchemaDotOrgUiApiTest
- Break the UI test into smaller tests.

Subtyping and field groups
- Ongoing
- Review UX and workflow
- Review all description.
- Determine the recommended types per entity type.
- Build out the default schema types properties.
- Review patterns and tests.

Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
to support subtype.

Wrap example in <code> tag "...in the format <code>example</code>."

- https://www.lullabot.com/articles/write-better-code-typed-entity

# Testing

- JavaScript test coverage for UI and Report.
- FormValidation test coverage for SchemaDotOrgFormTrait

# Best Practices

If two properties can address similar use-cases, use the more common property.
- For example, Place can have an 'image' and 'photo'.
  It is simpler to use 'image'.

# TBD

- How do we implement common content types and components? @see TYPES.md

- Should you be able to map the same field to multiple properties?
  - body => description and disambiguatingDescription

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?

- Should all the fields be prefixed with schema_* or field_*?
