Schema.org Blueprints
---------------------

# Todo

Test coverage

- Improve \Drupal\Tests\schemadotorg_ui\Kernel\SchemaDotOrgApiTest
- Improve \Drupal\Tests\schemadotorg\Functional\SchemaDotOrgSettingsElementTest
- JavaScript test coverage for UI and Report.
- Write tests schemadotorg_mapping_set.module
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

# TBD

What field types won't easily work or map to Schema.org?
- text with summary and formatting
- Date range
- Repeating events (SmartDates)

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module
  
