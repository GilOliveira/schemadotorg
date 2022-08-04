Table of contents
-----------------

* Introduction
* Features
* Configuration


Introduction
------------

The Schema.org Blueprints Taxonomy module provides mappings from 
taxonomy vocabularies and terms to <https://schema.org/DefinedTermSet> 
and <https://schema.org/DefinedTerm>.


Features
--------

- Maps terms and vocabularies to Schema.org DefinedTerm and DefinedTermSet or
  CategoryCode and CategoryCodeSet. 
- For JSON-LD, return a term's name when associated vocabulary does not have a 
  Schema.org mapping.
- Adds links to JSON-LD preview to the JSON-LD Vocabulary endpoint. 

  
Configuration
-------------

- Go to the Schema.org properties configuration page.
  (/admin/config/search/schemadotorg/settings/properties)
- Enter Schema.org properties that should be mapped to a vocabulary.
