Table of contents
-----------------

* Introduction
* Features
* Requirements
* Configuration
* FAQ


Introduction
------------

The Schema.org Blueprints JSON:API module builds on top of the JSON:API 
and JSON:API extras modules to apply Schema.org type and property mappings 
to JSON:API resources.


Features
--------

- Automatically create JSON:API endpoints for Schema.org type mappings.
- Automatically enable Schema.org properties for JSON:API endpoints.
- Automatically rename JSON:API entity and field names to use corresponding 
  Schema.org types and properties.
- Add JSON:API column with links to the Schema.org mappings admin page. 
  (@see /admin/config/search/schemadotorg)
- Enable to JSON:API file endpoint.


Requirements
------------

**[JSON:API Extras](https://www.drupal.org/project/jsonapi_extras)**
Provides a means to override and provide limited configurations to the default 
zero-configuration implementation provided by the JSON:API in Core.


Configuration
-------------

- Go to Schema.org JSON:API configuration page.
  (@see /admin/config/search/schemadotorg/settings/jsonapi)
- Enter fields that should default to enabled.
- Enter path prefixes to prepended to a Schema.org JSON:API resource.
- Disable/enable Schema.org JSON:API requirements checking.


FAQ
---

**Why does this module strongly recommend all resource that don't have a 
  matching enabled resource config and Schema.org type to be disabled?**

By default, every entity and field in Drupal is exposed via JSON:API with 
access controls.  Generally, it is best to only expose the data that  
consumers actually need.  Conceptually, only Schema.org type and properties
should be exposed via JSON:API with a few exceptions.
