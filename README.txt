Schema.org Blueprints
---------------------
Slides: https://www.slidescarnival.com/valentine-free-presentation-template/234

Provides blueprints for leveraging Schema.org to build and manage an SEO and API-first content architecture in Drupal.

# Sub modules

- Report - Provides a report for browsing Schema.org types, properties, and naming conventions.
- Descriptions
- UI
- Json-ld
- Json API
- Templates - Provides templates for creating and updating a Schema.org type in a click.
- Entity???

# Schema.org Type => Drupal Entity

- Thing => Node
- Enumeration => Term
- Media Object => Media
- Structure values => Paragraph
- Component => Block content

# Todo

- Write baseline tests

- Schema.org UI
  - Review Field UI
  - Add 'Create Schema.org type'
  - Add 'Manage Schema.org' tab
  - Create Schema.org type
    - schemadotorg.type
    - schemadotorg.description
  - Autocomplete
  - Description
  - Task and menu item

Schema.org Templates
  - Templates will preselect recommended fields.
  - Templates can be automatically updated

- Manage Schema.org UI

Type settings

Current properties

Available properties

--------------------------------------------------------------------------------

- Move abbeviation, prefixes, and suffixes into configuration.

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

- Third party settings
  - schemadotorg.property = name|alternateName
  - schemadotorg.description = optional|before|after|disable
  - schemadotorg.type = Thing|CreativeWork

- Schema.org UI (/admin/structure/types)
  - Add Schema.org type
  - Add Schema.org property
  - Add Schema.org property
  - Manage Schema.org properties

# Releases

Beta

- Finalize drupal machine names.

# Tests

Services

- SchemaDotOrgInstaller.php
- SchemaDotOrgBuilder.php
- SchemaDotOrgManager.php
- SchemaDotOrgNames.php

Report

- Filter form.
- Confirm types.
- Confirm properties.
- Confirm things.
- Confirm intangibles.
- Confirm enumerations.
- Confirm data types.
- Confirm names.
- Confirm warning.

# TBD

- Should we prefix all schema field with schema_*? YES

- Should machine name be tied to field storage via third party settings? YES

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- How to handle translations for imported data?

- How can we validate the generated JSON-ld?

