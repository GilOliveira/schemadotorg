Schema.org Blueprints
---------------------

# Todo

Ongoing
- Determine the recommended types per entity type.
- Determine supported/recommend contributed modules
  - Key value field
  - Flex field
- Build out the default schema types properties.
- Review patterns and tests.

Documentation
- Create README.md
- Add hook_help() to Schema.org Structure and Reports.

Code
- Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
  to support subtype.

Research
- https://www.lullabot.com/articles/write-better-code-typed-entity
- https://www.drupal.org/project/erd
- https://www.drupal.org/project/yild
- https://www.wikidata.org/wiki/Wikidata:Introduction
- https://iptc.org/

# Test  coverage

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
