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
- Determine the 'Type' field description.

# TBD

- Should subtype field be tracked?
  Makes is easier to query.
  Makes it possible to delete the field.

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
