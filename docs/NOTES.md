Schema.org Blueprints
---------------------

# Todo

- Possibly do not use range includes reference selection for
  taxonomy term relationship.

Tests
- Write tests schemadotorg_jsonld_embed.module
- Write tests schemadotorg_flexfield.module
- Write tests schemadotorg_inline_entity_form.module

## Ongoing

- Research and document recommended modules.
- Determine the recommended types per entity type.
  - Document tested and supported default Schema.org types.
  - Always review default Schema.org type properties.
  - Can we provide sector specific demos?
- Build out the default schema types properties.
- Review patterns and tests.

Code
- Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
  to support subtype.

# Backlog

Ajax
- Add Ajax to mapping form add field UI/UX
  - @see \Drupal\jsonapi_extras\Form\JsonapiResourceConfigForm

# Research

- https://www.drupal.org/docs/drupal-apis/entity-api/dynamicvirtual-field-values-using-computed-field-property-classes
- https://www.lullabot.com/articles/write-better-code-typed-entity
- https://www.drupal.org/project/yild
- https://www.wikidata.org/wiki/Wikidata:Introduction
- https://iptc.org/

# Ideas

- Schema.org Blueprints Demo module
  - Recipes to create sector specific demos
  - Includes all dependencies.
  - Generates dummy content.
  - Possibly mirror Umami (@see Contenta)
  - Demos could be Drush commands
  - `drush schemadotorg-demo personal`
  - Demos
    - Personal website
    - Company website
    - Restaurant website
    - Dealership website
    - Hotel website
    - Hospital website
    - University website

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
- Improve \Drupal\Tests\schemadotorg\Functional\SchemaDotOrgSettingsElementTest
- JavaScript test coverage for UI and Report.

# TBD

## General

What field types won't easily work or map to Schema.org?
- text with summary and formatting
- Date range
- Repeating events (SmartDates)

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

## JSON:API

- Should/could we add a link to JSON-LD (jsonld) via the JSON:API meta information?
  - https://www.drupal.org/project/drupal/issues/3100732

- Should field sub-properties for address and flexfield be converted to camelCase?
  - Seems unnecessary.

## JSON-LD

- Should the JSON-LD preview be moved to a configurable block?

## Schema.org

What Schema.org types should we document?
- Thing
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
