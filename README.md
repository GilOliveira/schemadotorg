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

-------------------


Drush commands

Add help to types and properties reports.
- Help text will better define the functionality.
- Note Schema.org version.
- Link to source CSV.

Fix devel generate
- Start simple and determine what is broken.

--------------------------------------------------------------------------------

Ongoing
- Determine the recommended types per entity type.
- Build out the default schema types properties.
- Build out the unlimited property list.

Subtyping
- schemadotorg_subtype use Thing
- Add subtyping to entity type.
  - Sub typing allows content editors to specify a more specific type for an entity.
  - For example, an Event can be subtyped to be a BusinessEvent, CourseInstance, EducationEvent, FoodEvent, etc...
  - Subtype properties can be included via condition logic.

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

- Why are we seeing 1329 types? (/admin/reports/schemadotorg/docs/types)

- How to handle translations for imported data?

- How can we validate the generated JSON-LD?

# Drush

Quick start

```
drush schemadotorg:create-type -y media AudioObject DataDownload ImageObject VideoObject
drush schemadotorg:create-type -y paragraph ContactPoint PostalAddress
drush schemadotorg:create-type -y user Person
drush schemadotorg:create-type -y node Person Organization Place Event CreativeWork
```

# References

Drupal Entities & Field

- [Drupal content entity 8.0.pages](https://paperzz.com/doc/7052675/drupal-content-entity-8.0.pages)
- [Defining and using Content Entity Field definitions](https://www.drupal.org/docs/drupal-apis/entity-api/defining-and-using-content-entity-field-definitions)

Related Issues

- [Issue #2152459: \[Policy\] Deprecate RDF module and move it to contrib](https://www.drupal.org/project/ideas/issues/2152459)

# Contrib modules

Required
- https://www.drupal.org/project/paragraphs

Recommended
- https://www.drupal.org/project/key_value_field
- https://www.drupal.org/project/field_token_value
- https://www.drupal.org/project/flexfield
- https://www.drupal.org/project/field_group

Other

- https://www.drupal.org/project/computed_field
- https://www.drupal.org/project/entity_reference_override
- https://www.drupal.org/project/field_tools
