Table of contents
-----------------

* Introduction
* Features
* Requirements
* Configuration
* References
* Notes
* Todo


Introduction
------------

The **Schema.org Blueprints Layout Paragraphs** provides integration with
the Layout Paragraphs module.


Features
--------

- Provides a 'Layout' paragraph.
- Exposes the 'Layout' paragraph behavior settings to JSON:API.
- Automatically adds and configures layout paragraphs field storage,
  instance, form display, and view display.
- Configure paragraph libraries support when the 'Layout Paragraphs Library'
  module is enabled.


Requirements
------------

**[Layout Paragraphs](https://www.drupal.org/project/layout_paragraphs)**  
Field widget and formatter for using layouts with paragraph fields.


Configuration
-------------

- Go to the Schema.org types configuration page.
  (/admin/config/search/schemadotorg/settings/types)
- Enter Schema.org types that default to using layout paragraphs.
- Enter the default paragraph types to be using with in layout paragraphs.


References
----------

- [Talking Drupal #337 - Layout Paragraphs](https://www.talkingdrupal.com/337)
- [Decoupling Acromedia.com with Drupal 9 & React](https://www.acromedia.com/article/decoupling-acromediacom-with-drupal-9-react)
- [Paragraphs vs Layout Builder in Drupal](https://www.mediacurrent.com/videos/paragraphs-vs-layout-builder-drupal)
- [Layout Paragraphs: A new way to manage Paragraphs](https://www.morpht.com/blog/layout-paragraphs-new-way-manage-paragraphs)


Notes
-----

- Other types of paragraphs
  - Headline
  - List
  - Table
  - Group
  - Embed (Block, Custom block, View, Webform)


Todo
----

- Rework layout paragraphs field weighting and grouping.
  @see schemadotorg_layout_paragraphs_entity_form_display_presave()

