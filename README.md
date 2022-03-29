Schema.org Blueprints
---------------------
Slides: https://www.slidescarnival.com/valentine-free-presentation-template/234

Provides blueprints for leveraging Schema.org to build and manage an SEO and API-first content architecture in Drupal.

The goal is to demo a content building framework that allows for progressive enhancements.

# Demo
- Create User:Person via UI
  - Note that contact point is use content when it should be using a paragraph.
- Create Media:Image via UI
- Create Media:* via Drush
- Create Paragragh:* via Drush
- Create Node:Organization via UI

- What is needed for solid demo?
  - Work example types created in the correct order.
- Example models.
- Dummy content.

# Todo

Settings
- Move 'schema_' field prefix into configuration
  - \Drupal\schemadotorg\SchemaDotOrgEntityTypeManager
- Add settings tab to 'Schema.org mappings'
- NestedList
- SettingsForm
  - Types
  - Properties
  - Names

Remove unneeded type vocabularies
- Keep Enumeration

-------------------


default_schema_type_properties:
  ContactPoint:
    - name
    - alterName
  Person:
    - name
    - alterName

-------------------

Add help to types and properties reports.
- Help text will better define the functionality.
- Note Schema.org version.
- Link to source CSV.

Fix devel generate
- Start simple and determine what is broken.

--------------------------------------------------------------------------------

Ongoing
- Determine the recommended types.
- Build out the default property list.
- Build out the global unlimited property list.

Subtyping
- schemadotorg_subtype
- Add subtyping to entity type.
  - Sub typing allows content editors to specify a more specific type for an entity.
  - For example, an Event can be subtyped to be a BusinessEvent, CourseInstance, EducationEvent, FoodEvent, etc...
  - Subtype properties can be included via condition logic.

Schema.org Templates
  - Templates will preselect recommended fields.
  - Templates can be automatically updated

# Concepts

Types of relationships
- Datatype
- Name/Value
- DefinedTerm
- Type

Schema.org Type => Drupal Entity

- Thing => Node
- Enumeration => Term
- Media Object => Media
- Structure values => Paragraph
- Component => Block content

--------------------------------------------------------------------------------

# Releases

Alpha
- Finalize drupal machine names.


# TBD

- Should Drupal names and ids be stored in the database or dynamically generated?

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

- Should the schema type be added to terms as field or property?

- Should we prefix all schema field with schema_*? YES

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- Should we allow multiple Schema type mapping per entity type?

- How to handle translations for imported data?

- How can we validate the generated JSON-LD?

# References

Drupal Entities & Field

- [Drupal content entity 8.0.pages](https://paperzz.com/doc/7052675/drupal-content-entity-8.0.pages)
- [Defining and using Content Entity Field definitions](https://www.drupal.org/docs/drupal-apis/entity-api/defining-and-using-content-entity-field-definitions)

Related Issues

- [Issue #2152459: \[Policy\] Deprecate RDF module and move it to contrib](https://www.drupal.org/project/ideas/issues/2152459)

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
