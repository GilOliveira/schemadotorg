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
- Build out the unlimited property list.

Subtyping
SchemaDotOrgTypeSelection => SchemaDotOrgRangeIncludesSelection
SchemaDotOrgEnumerationSelection => SchemaDotOrgEnumerationSelection
SchemaDotOrgEnumerationSelection => SchemaDotOrgEnumerationSelection

- Field is called schema_type use Thing
- Enable subtyping for @type type.
  - If checked, a custom 'Type' field will be added to the entity that allow content authors to specify a more specific type for an entity.
  - For example, an Event can be subtyped to be a BusinessEvent, CourseInstance, EducationEvent, FoodEvent, etc...
  - Subtypes are pulled from the @vocabulary
  - Only published subtypes will be displayed
  - Subtype properties can be included via condition logic.
  - SchemaDotOrgTypeSelection => SchemaDotOrgTypeRangeIncludesSelection


# TBD

- What default for field/properties should be configurable
  - How to handle unlimited fields?
  - Allow unlimited to be specified via drush???

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

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?

- Should all the fields be prefixed with schema_* for field_*?
