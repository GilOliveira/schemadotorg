Table of contents
-----------------

* Introduction
* Features
* Requirements
* Configuration
* Notes


Introduction
------------

The **Schema.org Blueprints Metatag module** automatically adds a metatag field
to Schema.org types.


Features
--------

- Automatically adds a metatag field to Schema.org types.
- Allows administrator to decide what meta tags and robots directive should
  be available on node edit forms.
- Tweaks the meta tag widget's description to work better with the Gin admin
  theme.


Requirements
------------

**[Metatag](https://www.drupal.org/project/metatag)**  
Manages meta tags for all entities.


Configuration
-------------

- Go to the Schema.org properties configuration page.
  (/admin/config/search/schemadotorg/settings/properties)
- Enter allowed meta tags by name to be displayed on node edit forms.
- Check robots directives to be displayed on node edit forms.


Notes
-----

- [Issue #3108108: Allow which metatags are visible on the field widget to be editable](https://www.drupal.org/project/metatag/issues/3108108)
