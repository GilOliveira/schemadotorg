Schema.org Blueprints
---------------------

# Todo

Tests
- Write tests schemadotorg_flexfield.module

## Ongoing

- Research and document recommended modules.
- Determine the recommended types per entity type.
  - Document tested and supported default Schema.org types.
  - Always review default Schema.org type properties.
- Build out the default schema types properties.
- Review patterns and tests.

Code
- Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
  to support subtype.

# Backlog

Ajax
- Add Ajax to mapping form add field UI/UX
  - @see \Drupal\jsonapi_extras\Form\JsonapiResourceConfigForm
  
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

What field types won't easily work or map to Schema.org?
- text with summary and formatting
- Date range
- Repeating events (SmartDates)

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module
  
