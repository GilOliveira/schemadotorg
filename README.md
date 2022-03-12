Schema.org Blueprints
---------------------
Slides: https://www.slidescarnival.com/valentine-free-presentation-template/234

Provides blueprints for leveraging Schema.org to build and manage an SEO and API-first content architecture in Drupal.

# Todo

SchemaDotOrgMapping
- schemadotorg.mapping.{entity_type}.{bundle}
- EntityDisplayBase

schemadotorg.mapping.entity_type.bundle:
  type: SchemaType
  properties:
    field_name: propertyType

Change Schema.org type (for bundles only).
- Add Change Schema type button
- Add Remove Schema
- Redirect to ?type= which force Schema selection.

How do we map field sub values?
- body.summary

--------------------------------------------------------------------------------

- Add descriptions.

- Entity Reference selection widget

- Write baseline tests

Configure Schema.org and Schema.org UI
- General
  - Field prefix: 'schema_'
  - Excluded: Types
- Names:
  - Abbreviations
  - Suffixes
  - Prefixes
- Fields:
  - Support types
  - Support base fields

Schema.org Templates
  - Templates will preselect recommended fields.
  - Templates can be automatically updated

--------------------------------------------------------------------------------

- Add help to types and properties reports.
  - Note Schema.org version.
  - Link to source CSV.

- Add reports for targeted entity types
  - node
  - paragraphs
  - terms

- Define what is alpha beta and release goals.

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

UI
FieldUIRouteTest.php

# TBD

- How do we handle property.name vs entity.title?
  - Allow title to be mapped to name?

- Should the schema type be added to terms as field or property?

- Should we prefix all schema field with schema_*? YES

- Should machine name be tied to field storage via third party settings? YES

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- How to handle translations for imported data?

- How can we validate the generated JSON-ld?

# Notes/Decisions

- Opting not to extend \Drupal\Core\Entity\EntityForm because it adds complexity and unpredictability.

# References

- https://paperzz.com/doc/7052675/drupal-content-entity-8.0.pages
- https://www.drupal.org/docs/drupal-apis/entity-api/defining-and-using-content-entity-field-definitions

# APIs

- **entity_type.manager:**
  Manages entity type plugin definitions.
  _Provides entity definition, handlers, storage, etc...
- **entity_type.repository:**
  Provides helper methods for loading entity types.
  _Gets entity types as options_
- **entity_type.bundle.info**
  Provides discovery and retrieval of entity type bundles.
  _Gets entity bundles_
- **entity.repository:**
  Provides several mechanisms for retrieving entities.
- **entity_display.repository:**
  Provides a repository for entity display objects (view modes and form modes).
- **entity_field.manager:**
  Manages the discovery of entity fields. This includes field definitions, base field definitions, and field storage definitions.


# Sub modules

- Report - Provides a report for browsing Schema.org types, properties, and naming conventions.
- Descriptions
- UI
- Json-ld
- Json API
- Templates - Provides templates for creating and updating a Schema.org type in a click.
- Entity???

# Contrib modules

Required

- https://www.drupal.org/project/paragraphs
- https://www.drupal.org/project/key_value_field

Recommended
- https://www.drupal.org/project/entity_type_clone
- https://www.drupal.org/project/convert_bundles

Other
- https://www.drupal.org/project/field_name_prefix_remove
- https://www.drupal.org/project/flexfield
- https://www.drupal.org/project/properties
- https://www.drupal.org/project/computed_field

# Schema.org Type => Drupal Entity

- Thing => Node
- Enumeration => Term
- Media Object => Media
- Structure values => Paragraph
- Component => Block content
