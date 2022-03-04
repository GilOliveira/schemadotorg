Schema.org
----------

# Sub modules

- Report
- Descriptions
- UI
- Json-ld
- Json API
- Entity???

# Schema.org Type => Drupal Entity

- Thing => Node
- Enumeration => Term
- Media Object => Media
- Structure values => Paragraph
- Component => Block content

# Todo

- Custom labels

- Write baseline tests

--------------------------------------------------------------------------------

- Add help to types and properties reports.
  - Note version.
  - Link to source CSV.

- Add reports for targeted entity types
  - node
  - paragraphs
  - terms

- Create type blacklist which applies to taxonomy.

- Define what is alpha beta and release goals.

- Use Entity Builder class to add helper
  @see https://www.drupal.org/node/3191609

# Tests

Installer


Utility

- SchemaDotOrgStringHelper
  - Possibly use all labels in tests.

Report
- Confirm types.
- Confirm properties.
- Confirm things.
- Confirm intangibles.
- Confirm enumerations.
- Confirm data types.
- Confirm names.
- Confirm warning.

# TBD

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- Should we prefix all schema field with schema_* ?

- Should machine name be tied to field storage via third party settings?

- How to handle translations for imported data?

- How can we validate the generated JSON-ld?

