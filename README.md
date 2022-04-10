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

Recommended Types
- Healthcare
- Restaurant
- Education
- Government
- Automotive
- Commerce
- Entertainment
- Publishing

Field group and ordering
- Warning about updating field types.

Create SchemaDotOrgUiMappingsAddForm
- entity.schemadotorg_mapping.add_group
- /admin/structure/schemadotorg/add/{group}

- Add Schema.org @group types
- Table select
  - Type/Description/Properties

Tests

Kernel
- \Drupal\schemadotorg_ui\SchemaDotOrgUiApi::createTypeValidate
- \Drupal\schemadotorg_ui\SchemaDotOrgUiApi::deleteTypeValidate
Functional
- \Drupal\schemadotorg_ui\SchemaDotOrgUiApi::createType
- \Drupal\schemadotorg_ui\SchemaDotOrgUiApi::deleteType

- Field groups on create and updated.
- Break the UI test into smaller tests.
- \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage::getDefaultFieldGroups
- \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage::getDefaultFieldGroupFormatType
- \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage::getDefaultFieldGroupViewType

- Ongoing
- Review UX and workflow
- Review all description.
- Determine the recommended types per entity type.
- Build out the default schema types properties.
- Review patterns and tests.

Improve \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::calculateDependencies
to support subtype.

Add test to cover schemadotorg_field_config_load() to RangeInclude selection

# Best Practices

If two properties can address similar use-cases, use the more common property.
- For example, Place can have an 'image' and 'photo'.
  It is simpler to use 'image'.

# TBD

- How do we implement common content types and components? @see TYPES.md

- Should you be able to map the same field to multiple properties?
  - body => description and disambiguatingDescription

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?

- Should all the fields be prefixed with schema_* or field_*?
