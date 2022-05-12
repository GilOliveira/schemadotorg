Schema.org Blueprints
---------------------

# Todo

- Add key/help to mapping create page.

- JSON:API Extras Tests.
  - Add comments to SchemaDotOrgJsonApiExtras.php.
  - SchemaDotOrgJsonApiExtrasTest
  - SchemaDotOrgJsonApiExtrasMappingListBuilderTest

Ajax
- Add Ajax to mapping form add field UI/UX
  - @see \Drupal\jsonapi_extras\Form\JsonapiResourceConfigForm

Ongoing
- Determine the recommended types per entity type.
- Determine supported/recommend contributed modules
  - Key value field
  - Flex field
- Build out the default schema types properties.
- Review patterns and tests.

Code
- Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
  to support subtype.

Research
- https://www.drupal.org/project/double_field
- https://www.lullabot.com/articles/write-better-code-typed-entity
- https://www.drupal.org/project/erd
- https://www.drupal.org/project/yild
- https://www.wikidata.org/wiki/Wikidata:Introduction
- https://iptc.org/

# Ideas

- Schema.org Blueprint Configuration module
  - Established the best practice of collecting configuration settings in a
    field type
  - Configuration field type is automatically included via APIs.
  - Configuration field data is simple key/values.
  - Configuration field data is not displayed on the page.
  - Add Configuration details to the entity edit form
  - Create a Schema.org type specific flexfield.
  - Label 'Custom configuration'
  - Can be preconfigured and altered as needed.


- Automatically generate corresponding View for Schema.org mapping (node and media)
  - Huge amount of work to understand the Views entity and APIs.
  - Each Schema.org type could be automatically added below the 'Content' item.
  - Maybe a default admin view could be setup and cloned
  - Fields
    - Label
    - Type
    - Subtype
    - Enumeration
    - Terms
    - Keywords
    - startDate
    - endDate

- Schema.org Blueprint Speakable module
  - https://schema.org/speakable
  - Unlimited text field which can be filled with CSS selectors
  - Adds 'Set speakable context link' to page.
  - Selector can be set via sidebar that highlights and tracks what is being
    clicked.
  - Algorithm determines the unique selector.
  - Sidebar has a Save button
  - JS blocks all click events
  - Speakable preview would pull the CSS selector text into a report/table

# Test coverage

- Improve \Drupal\Tests\schemadotorg_ui\Kernel\SchemaDotOrgUiApiTest
- Create SchemaDotOrgUiMappingForm kernel test.
  - Check all functionality.
- JavaScript test coverage for UI and Report.
- FormValidation test coverage for SchemaDotOrgFormTrait

# TBD

What Schema.org types should we document?
- Recipe
- Restaurant
- Logging business: Hotel
- Person
- LocalBusiness
- Physician
- Movie
- Blogpost
- CreativeWork: FAQ
- HowTo

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?

- How do we handle Schema.org types being used in multiple entity types?
  (i.e. node:Person and user:Person)
  - Recommend that people do not this
  - Require them to manually alter there API types and paths

- Should Schema.org JSON:API resources path and type be lowercase?
