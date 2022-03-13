Schema.org Blueprints
---------------------
Slides: https://www.slidescarnival.com/valentine-free-presentation-template/234

Provides blueprints for leveraging Schema.org to build and manage an SEO and API-first content architecture in Drupal.

Demo

- Report
- Mappings
- Creation
  - Person
  - Thing > Event
  - Thing > Location
- Form and view display

# Todo

The goal is to demo framework which allows for progressive enhancements.

Report


Add validation rules.
- Entity bundle exists
- Schema type exists.

Recommend specific types based on the entity.
- Unlink existing types.

Block media type from being added  @see \Drupal\media\MediaTypeForm::form

Should we change mapping targetEntityType to target_entity_type?

-----

Paragraphs
Media
Block content

- Write baseline tests

Done!!!! for now

--------------------------------------------------------------------------------

Mapping
- property => field or field => property
- Sub properties???

SchemaDotOrgMapping config entity
- Revisit dependencies.

- Reuse \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm::save

- Suppress superseded properties, unless they are being used.

- Tree widget
  - https://github.com/vakata/jstree

- Entity Reference selection widget

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

- Add help to types and properties reports.
  - Note Schema.org version.
  - Link to source CSV.

Define what is alpha beta and release goals.

--------------------------------------------------------------------------------

# Releases

Alpha
- Finalize drupal machine names.
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

- How do we handle property.name vs entity.title?
  - Allow title to be mapped to name?

- Should the schema type be added to terms as field or property?

- Should we prefix all schema field with schema_*? YES

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- How to handle translations for imported data?

- How can we validate the generated JSON-LD?

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
- https://www.drupal.org/project/field_ui_extras

Other
- https://www.drupal.org/project/field_name_prefix_remove
- https://www.drupal.org/project/flexfield
- https://www.drupal.org/project/properties
- https://www.drupal.org/project/computed_field
- https://www.drupal.org/project/base_field_override_ui
- https://www.drupal.org/project/field_token_value

# Schema.org Type => Drupal Entity

- Thing => Node
- Enumeration => Term
- Media Object => Media
- Structure values => Paragraph
- Component => Block content
