Table of contents
-----------------

* Introduction
* Features
* Configuration


Introduction
------------

The **Schema.org Blueprints Identifier** module manages identifiers 
(https://schema.org/identifier) for Schema.org types.


Features
--------

- Allows base fields (i.e. uuid) to be used as a Schema.org identifier.
- Allows dedicated identifier fields to be created with Schema.org type.
- Adds identifier field values to JSON-LD.
- Exposes identifier fields to JSON:API.


Configuration
-------------

- Go to the Schema.org properties configuration page.
  (/admin/config/search/schemadotorg/settings/properties)
- Enter the field prefix to be prepended to a Schema.org identifiers, without a 
  field name, when added to an entity type.
- Enter identifier field definitions which will be available to 
  Schema.org types.
- Enter Schema.org types and their identifiers.
