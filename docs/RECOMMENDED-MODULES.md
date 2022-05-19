Schema.org Blueprints
---------------------

_Template_

- **[Module Name](https://www.drupal.org/)**
(Applies to:
[propertyName](https://schema.org/propertyName),
[propertyName](https://schema.org/propertyName))
{description}
_{notes}_


## Recommended Core Modules

- [Email](https://www.drupal.org/docs/8/core/modules/email)
- [Datetime](https://www.drupal.org/docs/8/core/modules/datetime)
- [Link](https://www.drupal.org/docs/8/core/modules/liunk)
- [Media](https://www.drupal.org/docs/8/core/modules/media)
- [Media Library](https://www.drupal.org/docs/8/core/modules/media_library)
- [Telephone](https://www.drupal.org/docs/8/core/modules/telephone)

## Recommended Contribute Modules

### UI/UX

_The below modules improve the Schema.org Blueprints module's user experience._

- [Field Group](https://www.drupal.org/project/field_group)

### Field Collections

_The below modules provide different ways to create and manage a collection of fields._

- [Paragraphs](https://www.drupal.org/project/paragraphs)
- [Inline Entity Form](https://www.drupal.org/project/inline_entity_form)
- [FlexField](https://www.drupal.org/project/flexfield)

### Field Types

_The below modules provide more specific field types and behaviors._


Date/time

- [Time Field](https://www.drupal.org/project/time_field)
- [SmartDate](https://www.drupal.org/project/smart_date)

Numeric

- [Range](https://www.drupal.org/project/range)

Demographic

- [Gender](https://www.drupal.org/project/gender)

Location

- [Address](https://www.drupal.org/project/address)

Telephone

- [International Phone Field](https://www.drupal.org/project/international_phone)
- [Telephone Formatter](https://www.drupal.org/project/telephone_formatter)

Select

- [Select (or other)](https://www.drupal.org/project/select_or_other)
- [Select Text Value](https://www.drupal.org/project/select_text_value)

Composite

- [Double Field](https://www.drupal.org/project/double_field)
- [Key value field](https://www.drupal.org/project/key_value_field)


Calculation

- [Computed Field](https://www.drupal.org/project/computed_field)
- [Field Token Value](https://www.drupal.org/project/field_token_value)

# Roadmap

Pre-Alpha (Dev)
- Fix issue as the come up with or without issues or MRs.
- Plan for hooks.
- Define core dependencies.
- Implement core submodules.

Alpha
- Define baseline Schema.org types and properties
- Establish sub-modules and feature list
- Create hooks and APIs
- Ensure baseline test coverage
- Finalize core dependencies

Beta
- Find co-maintainers and sponsoring organizations
- Improve documentation with in-line help
- Finalize and document hooks and APIs
- Determine upgrade path between Schema.org versions
- Ensure regression test coverage

Stable
- Add Schema.org types and properties as needed
- Require tests coverage for all changes
- Provide additional enhancements via custom code or contrib modules

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
- Improve \Drupal\Tests\schemadotorg\Functional\SchemaDotOrgSettingsElementTest
- Create SchemaDotOrgUiMappingForm kernel test.
  - Check all functionality.
- JavaScript test coverage for UI and Report.

# TBD

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

- How do we handle sub-values (i.e. body.summary)?
  - Token field?

- How to handle translations for imported data?
  - Include descriptions added via the schemadotorg_descriptions.module

- How can we validate the generated JSON-LD?
  - Chrome extension and online

- Should we add a link to JSON-LD (jsonld) via the JSON:API meta information?
  - https://www.drupal.org/project/drupal/issues/3100732

