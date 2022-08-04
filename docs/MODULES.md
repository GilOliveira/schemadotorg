Schema.org Blueprints: Recommended core and contributed modules
---------------------------------------------------------------

Below are recommended core and contributed modules that can be used with the Schema.org Blueprints module.

## Sub-modules

_Below modules are included with the Schema.org Blueprints module/package._

Core

- **Schema.org Blueprints**  
  Uses Schema.org as the blueprint for the content architecture and structured data in a Drupal website.

- **Schema.org Blueprints UI**  
  Allows administrators to attach custom Schema.org properties to fieldable types.

- **Schema.org Blueprints Report**  
  Provides a report for browsing Schema.org types, properties, and naming conventions.

UI/UX

- **Schema.org Blueprints Descriptions**  
  Sets entity type and field descriptions to their corresponding Schema.org type and property comments.

- **Schema.org Blueprints Export**  
  Provides a CSV export of Schema.org type mappings.

- **Schema.org Blueprints Mapping Set**  
  Provides the ability to create a set of related Schema.org types via Drush.

- **Schema.org Blueprints Subtype**  
  Subtypes allow more specificity without having to create dedicated entity types for every appropriate Schema.type.

- **Schema.org Blueprints Translation**  
  Manages translations for Schema.org types and properties as they are created.

Fields

- **Schema.org Blueprints Flex Field**  
  Allows a Flex field to be used to create Schema.org relationships within an entity type/bundle Schema.org mapping.

- **Schema.org Blueprints Inline Entity Form**  
  Allows an inline entity form to be automatically added to Schema.org properties within an entity type/bundle Schema.org mapping.

Entities

- **Schema.org Blueprints Paragraphs**  
  Integrates the Paragraphs and Paragraphs Library module with the Schema.org Blueprints module.

- **Schema.org Blueprints Taxonomy**  
  Provides mappings from taxonomy vocabularies and terms to https://schema.org/DefinedTermSet and https://schema.org/DefinedTerm.

JSON-LD 

- **Schema.org Blueprints JSON-LD**  
  Adds Schema.org structured data as JSON-LD in the head of web pages.

- **Schema.org Blueprints JSON-LD Breadcrumb**  
  Add Drupal's breadcrumb to Schema.org JSON-LD for the current route.

- **Schema.org Blueprints JSON-LD Embed**  
  Extracts embedded media and content from an entity and includes the associated Schema.org type in JSON-LD.

- **Schema.org Blueprints JSON-LD Endpoint**  
  Provides an endpoint to get an entity's Schema.org JSON-LD.

- **Schema.org Blueprints JSON-LD Preview**  
  Allows users to preview a web page's Schema.org JSON-LD.

API

- **Schema.org Blueprints JSON:API**  
  Builds on top of the JSON:API and JSON:API extras modules to apply Schema.org type and property mappings to JSON:API resources.

- **Schema.org Blueprints JSON:API Preview**  
  Allows users to preview a web page's Schema.org JSON:API.

## Core Modules

_Below are recommended Drupal core modules._

- **[Datetime](https://www.drupal.org/docs/8/core/modules/datetime)**
  (Applies to: [Date](https://schema.org/Date), [DateTime](https://schema.org/DateTime))  
  Defines datetime form elements and a datetime field type.

- **[Link](https://www.drupal.org/docs/8/core/modules/link)**
  (Applies to: [URL](https://schema.org/URL))  
  Provides a simple link field type.

- **[Media](https://www.drupal.org/docs/8/core/modules/media)**
  (Applies to: [MediaObject](https://schema.org/MediaObject))  
  Manages the creation, configuration, and display of media items.

- **[Media Library](https://www.drupal.org/docs/8/core/modules/media_library)**
  (Applies to: [MediaObject](https://schema.org/MediaObject))  
  Enhances the media list with additional features to more easily find and use existing media items.

- **[Telephone](https://www.drupal.org/docs/8/core/modules/telephone)**
  (Applies to: [telephone](https://schema.org/telephone))  
  Defines a field type for telephone numbers.

## Contribute Modules

_Below are recommended contributed modules._

### UI/UX

_The below modules improve the Schema.org Blueprints module's user experience._

- **[Field Group](https://www.drupal.org/project/field_group)**  
  Provides the ability to group your fields on both form and display.

- **[Entity Browser](https://www.drupal.org/project/entity_browser)**
  Provide a generic entity browser/picker/selector.

- **[Content Browser](https://www.drupal.org/project/content_browser)**
  Provides a default Entity Browser for default Content Entity types, using Masonry.

- **[Inline Entity Form](https://www.drupal.org/project/inline_entity_form)** 
  Provides a widget for inline management (creation, modification, removal) of referenced entities.

### Field Collections

_The below modules provide different ways to create and manage a collection of fields._

- **[Paragraphs](https://www.drupal.org/project/paragraphs)**
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Enables the creation of paragraphs (i.e. component) entities.

- **[Entity Construction Kit (ECK)](https://www.drupal.org/project/eck)**
  (Applies to: [Intangible](https://schema.org/Intangible))  
  ECK (Entity Construction Kit) allows users to create and administer entity types, bundles and entities from UI.

- **[Micro-content](https://www.drupal.org/project/microcontent)**
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Provides a lightweight reusable content-entity and type that supports fields, revisions and translation.

- **[Storage](https://www.drupal.org/project/storage)**
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Lightweight, bundleable entities for storing structured data.

- **[FlexField](https://www.drupal.org/project/flexfield)**
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Defines a new "FlexField" field type that lets you create simple inline multiple-value fields without having to use entity references.

### Field Types

_The below modules provide more specific field types and behaviors._

Date/time

- **[Time Field](https://www.drupal.org/project/time_field)**
  (Applies to: [Time](https://schema.org/Time))  
  Provides "Time Field" and "Time Range Field".

- **[SmartDate](https://www.drupal.org/project/smart_date)**
  (Applies to: [Date](https://schema.org/Date), [DateTime](https://schema.org/DateTime), [Schedule](https://schema.org/Schedule))  
  Provides the ability to store start and end times, with duration. Also provides an intelligent admin UI, and a variety of formatting options.

Numeric

- **[Numeric Range](https://www.drupal.org/project/range)** 
  (Applies to: [typicalAgeRange](https://schema.org/typicalAgeRange))  
  Defines a numeric range field type.

Demographic

- **[Gender](https://www.drupal.org/project/gender)**
  (Applies to: [GenderType](https://schema.org/GenderType), [gender](https://schema.org/gender))  
  Provides inclusive options for collecting gender information of individuals.

Location

- **[Address](https://www.drupal.org/project/address)**
  (Applies to: [PostalAddress](https://schema.org/PostalAddress), [address](https://schema.org/address))  
  Provides functionality for storing, validating and displaying international postal addresses.

Telephone

- **[Telephone Formatter](https://www.drupal.org/project/telephone_formatter)**
  (Applies to: [telephone](https://schema.org/telephone))  
  Provides extra formatter to core's Telephone field

Options

- **[Select (or other)](https://www.drupal.org/project/select_or_other)**
  (Applies to: [Enumeration](https://schema.org/Enumeration))  
  Provides a select box form element with additional option 'Other' to give a textfield.

- **[Select Text Value](https://www.drupal.org/project/select_text_value)**
  (Applies to: [Enumeration](https://schema.org/Enumeration))  
  Widget for text fields to offer pre-define values for selection.

Composite

- **[Double Field](https://www.drupal.org/project/double_field)**
  (Applies to: [Intangible](https://schema.org/Intangible), [identifier](https://schema.org/identifier))  
  Provides a field type with two separate sub-fields.

- **[Key value field](https://www.drupal.org/project/key_value_field)**
  (Applies to: [Intangible](https://schema.org/Intangible), [PropertyValue](https://schema.org/PropertyValue), [identifier](https://schema.org/identifier))   
  Provides a field with a key, value pair along with a description.

Calculation

- **[Computed Field](https://www.drupal.org/project/computed_field)**  
  Defines a field type that allows values to be "computed" via PHP code.

- **[Field Token Value](https://www.drupal.org/project/field_token_value)**  
  Provides a field allowing the value to be set using a string containing tokens.
