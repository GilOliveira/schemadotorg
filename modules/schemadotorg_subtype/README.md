Table of contents
-----------------

* Introduction
* Features
* Configuration


Introduction
------------

The Schema.org Blueprints Subtype module allows more specificity without 
having to create dedicated entity types for every appropriate Schema.type.

For example, the subtypes for <http://schema.org/Event> are mainly for adding a 
little extra specificity about an Event. Most event subtypes do not need to 
have dedicated content types created.


Features
--------

- Adds Enable Schema.org subtyping to Schema.org mapping UI.
- Allows site builders to alter subtype field name, label, description, and 
  allowed values.
- Adds custom 'subtype' mapping to Schema.org mapping properties.
- Alters the Schema.org mapping list builder and adds a 'Subtype' column.


Configuration
-------------

- Go to /admin/config/search/schemadotorg/settings/subtype
- Enter default field suffix used for subtype field machine names.
- Enter default label used for subtype fields.
- Enter the default description used for subtype fields.
- Enter Schema.org types that support subtyping by default.
