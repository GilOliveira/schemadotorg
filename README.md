Schema.org Blueprints
---------------------
Slides: https://www.slidescarnival.com/valentine-free-presentation-template/234

Provides blueprints for leveraging Schema.org to build and manage an SEO and API-first content architecture in Drupal.

# Todo

The goal is to demo a content building framework which allows for progressive enhancements.

Person

Types of relationships
- Datatype
- Name/Value
- DefinedTerm
- Type

Review and document patterns provide by Google
https://developers.google.com/search/docs/advanced/structured-data/intro-structured-data

Add help to types and properties reports.
- Help text will better define the functionality.
- Note Schema.org version.
- Link to source CSV.

Display Enumerations as select menus.

Change target for entity reference based on available mappings.
- Find mappings with sub-targets.
- Display dedicated entity types.

drush soma - Schema.org map command
- drush soma user Person
- drush soma node Recipe
- drush soma media VideoObject
- Isolate form values and submit callback.

--------------------------------------------------------------------------------

Defined form and view display defaults.
- \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::alterFormDisplayWidget
- \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder::alterViewDisplayWidget

- Tree widget
  - https://github.com/vakata/jstree

- Subtyping.

Configure Schema.org and Schema.org UI
- General
  - Field prefix: 'schema_'
- Names:
  - Abbreviations
  - Suffixes
  - Prefixes

Schema.org Templates
  - Templates will preselect recommended fields.
  - Templates can be automatically updated

--------------------------------------------------------------------------------

# Releases

Alpha
- Finalize drupal machine names.

# Tests

Entity

SchemaDotOrgMapping
- \Drupal\KernelTests\Core\Entity\EntityDisplayFormBaseTest
- \Drupal\KernelTests\Core\Entity\EntityDisplayRepositoryTest

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
- FieldUIRouteTest.php

# TBD

- Should a warning/info message be displayed when creating a new mapping
  and entity?

- Should you be able to map the same field to multiple properties?
  - body => description and disambiguatingDescription

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- Should Drupal names and ids be stored in the database or dynamically generated?

- Should the schema type be added to terms as field or property?

- Should we prefix all schema field with schema_*? YES

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- Should we allow multiple Schema type mapping per entity type?

- How to handle translations for imported data?

- How can we validate the generated JSON-LD?

# References

Schema.org

- [Understand how structured data works | Google Search Central](https://developers.google.com/search/docs/advanced/structured-data/intro-structured-data)
- [Schema.org - What, How, Why? | Video](https://www.youtube.com/watch?v=hcahQfN5u9Y)
- [RDF in Drupal: What is it and Why Should We Care? \ Drupal Easy](https://www.drupaleasy.com/blogs/ultimike/2009/06/rdf-drupal-what-it-and-why-should-we-care)

Drupal Entities & Field

- [Drupal content entity 8.0.pages](https://paperzz.com/doc/7052675/drupal-content-entity-8.0.pages)
- [Defining and using Content Entity Field definitions](https://www.drupal.org/docs/drupal-apis/entity-api/defining-and-using-content-entity-field-definitions)

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

- Schema.org Report - Provides a report for browsing Schema.org types, properties, and naming conventions.
- Schema.org RDF - Integrates Schema.org mappings with Drupal core's RDF(a) mappings.
- Schema.org UI - Allows administrators to attach custom Schema.org properties to fieldable types.

TDB

- JSON-LD - Generation JSON-LD definitions for Schema.org type.
- JSON:API - Apply Schema.org type and property names to Drupal core's JSON:API.
- Templates - Provides templates for creating and updating a Schema.org type in a click.

# Contrib modules

Required
- https://www.drupal.org/project/paragraphs

Recommended
- https://www.drupal.org/project/key_value_field
- https://www.drupal.org/project/field_token_value

Other
- https://www.drupal.org/project/flexfield
- https://www.drupal.org/project/computed_field

TBD
- https://www.drupal.org/project/entity_type_clone
- https://www.drupal.org/project/convert_bundles
- https://www.drupal.org/project/base_field_override_ui
- https://www.drupal.org/project/jsonapi_node_preview_tab
- https://www.drupal.org/project/field_ui_extras

