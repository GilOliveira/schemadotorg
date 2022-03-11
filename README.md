Schema.org Blueprints
---------------------
Slides: https://www.slidescarnival.com/valentine-free-presentation-template/234

Provides blueprints for leveraging Schema.org to build and manage an SEO and API-first content architecture in Drupal.

# Todo

SchemaDotOrgMapping
- schemadotorg.mapping.{entity_type}.{bundle}
- EntityDisplayBase

entity_type.bundle:
  type: SchemaType
  properties:
    field_name: propertyType

Provide default mapping for entity types

Move mapping up to the entity and out of the field.

Allow schema.type to be selected for existing entities. THIS CAN BE DONE LATER.

How do we

- Load and note existing fields.

- Only allow access when there are third party settings.

- Mapping title, body, and author to Schema.org property

- Move property on config storage to the node type.
  - As fields are added and removed the mapping would be updated.

  - Default properties.

      name => title (Thing)
      description => body.value (Thing)
      disambiguatingDescription => body.summary (Thing)
      dataCreated => create (CreativeWork)
      dataUpdated => updated (CreativeWork)
      url => url (Thing)
      uid => author (CreativeWork)

-------

- Entity Reference selection widget


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

--------------------------------------------------------------------------------

- Move abbreviations, prefixes, and suffixes into configuration.

- Add help to types and properties reports.
  - Note Schema.org version.
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
  - schemadotorg.types = Thing|CreativeWork

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
