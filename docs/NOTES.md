Schema.org Blueprints
---------------------

# Todo

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
- https://www.lullabot.com/articles/write-better-code-typed-entity
- https://www.drupal.org/project/erd
- https://www.drupal.org/project/yild
- https://www.wikidata.org/wiki/Wikidata:Introduction
- https://iptc.org/

# Ideas

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

# Test coverage

- Improve \Drupal\Tests\schemadotorg_ui\Kernel\SchemaDotOrgUiApiTest
- Create SchemaDotOrgUiMappingForm kernel test.
  - Check all functionality.
- JavaScript test coverage for UI and Report.
- FormValidation test coverage for SchemaDotOrgFormTrait

# TBD

- Should you be able to map the same field to multiple properties?
  - body => description and disambiguatingDescription

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

- How do we handle JSON:API resources paths?
  - Known conflicts
    - node:Person user:Person
    - taxonomy:Thing node:Thing paragraph:Thing
  - Search for conflicting resource type by searching from properties.

