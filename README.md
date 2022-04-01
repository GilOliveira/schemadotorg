Schema.org Blueprints
---------------------

Improve form validation

- JSON:API
- \Drupal\jsonapi_extras\EventSubscriber\JsonApiBuildSubscriber

- Bonus types
  - Blog
  - FAQPage
  - QAPage
  - Movie
  - Article
  - Blog Post
  - Web Site
  - Web Page

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
