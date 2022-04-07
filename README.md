Schema.org Blueprints
---------------------

Todo
- Project page
- Dev release
- Tests
- Outline
- Blog post
- Screen cast

Improve form validation

Add test coverage to...

- Update schemadotorg_mapping_type.settings
- Update schemadotorg_mapping_type.settings schema
- Update MappingType list builder
- Update edit form using grouped list
- \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage::getEntityTypeBundleDefinitions
- \Drupal\schemadotorg_ui\Form\SchemaDotOrgUiMappingForm::buildEntityTypesForm

Ongoing
- Determine the recommended types per entity type.
- Build out the default schema types properties.
- Review all description.
- Review patterns and tests.

Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
to support subtype.

# TBD

- Should node.title map to both headline and name?
  - \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage::getBaseFieldMappings
  - \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage::getBaseFieldNames

- How do we implement common content types and components? @see TYPES.md

- Should you be able to map the same field to multiple properties?
  - body => description and disambiguatingDescription

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?

- Should all the fields be prefixed with schema_* or field_*?
