Schema.org Blueprints
---------------------

Below are recommended core and contribute modules that can be used with the Schema.org Blueprints module.

## Core Modules

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

### UI/UX

_The below modules improve the Schema.org Blueprints module's user experience._

- **[Field Group](https://www.drupal.org/project/field_group)**  
  Provides the ability to group your fields on both form and display.  

### Field Collections

_The below modules provide different ways to create and manage a collection of fields._

- **[Paragraphs](https://www.drupal.org/project/paragraphs)**
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Enables the creation of paragraphs (i.e. component) entities.

- **[Inline Entity Form](https://www.drupal.org/project/inline_entity_form)**
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Provides a widget for inline management (creation, modification, removal) of referenced entities.

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
