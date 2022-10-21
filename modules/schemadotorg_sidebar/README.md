Table of contents
-----------------

* Introduction
* Features
* Requirements


Introduction
------------

The **Schema.org Blueprints Sidebar** creates and manages paragraph types
that are displayed in the node edit form''s sidebar on every content type.'

This module makes it possible to create a collection of fields that are
centrally managed via a paragraph type while having these fields available on
every node edit form.  These 'sidebar' paragraph types can store
editorial information, messages, or custom setting that is not applicable
to Schema.org or JSON:API.


Features
--------

- Create an 'Editorial information' paragraph type that is added to every node.
- Displays customizable editorial related message on node edit forms.
- Hides empty paragraphs from being displayed.


Requirements
------------

**[Paragraphs](https://www.drupal.org/project/paragraphs)**  
Enables the creation of paragraphs entities.

**[Field Group](https://www.drupal.org/project/field_group)**  
Provides the ability to group your fields on both form and display.

**[Inline Entity Form](https://www.drupal.org/project/inline_entity_form)**  
Provides a widget for inline management (creation, modification, removal) of referenced entities.


Notes
-----

Currently, on a default 'editorial' paragraph is supported but this module could
be reworked to support a 'configuration' paragraph.
