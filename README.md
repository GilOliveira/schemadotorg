Schema.org Blueprints
---------------------

Improve form validation

- Bonus types
  - HowTo
  - FAQPage
  - QAPage

Ongoing
- Determine the recommended types per entity type.
- Build out the default schema types properties.

Subtyping
- Add settings
- Add Selection handler

- Field is called schema_type use Thing
- [Checkbox] Enable subtyping.
  - If checked, a custom 'Type' (schema_type) field is added to the entity
    which allows content authors to specify a more specific (sub)type for
    the entity.
  - Subtypes for @type included EventA, EventB, etc...
  - Subtypes are pulled from the @vocabulary.
  - Only published subtypes will be displayed

  - If checked, Subtyping is enabled.
  - schema_types.default_subtypes
    - Event
  - Add --enable-subtype to drush create-type command
  - Subtype properties can be included via condition logic.
  - SchemaDotOrgTypeSelection => SchemaDotOrgTypeRangeIncludesSelection


# TBD

- How do we implement common content types and components?
  - teaser
  - slideshow
  - lists
  - forms
  - toc
  - faq
  - timeline

- Should you be able to map the same field to multiple properties?
  - body => description and disambiguatingDescription

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?

- Should all the fields be prefixed with schema_* or field_*?
