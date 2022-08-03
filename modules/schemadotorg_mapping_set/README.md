Table of contents
-----------------

* Introduction
* Features
* Configuration
* Notes


Introduction
------------

The Schema.org Blueprints Mapping Sets module provides the ability to create 
a set of related Schema.org types via Drush.


Features
--------

- Admin page to set up, generate, kill, and teardown mapping sets
- Drush commands to set up, generate, kill, and teardown mapping sets
- Configure custom mapping sets.


Configuration
-------------

- Go to the Schema.org Mapping Sets configuration page.
  (@see /admin/config/search/schemadotorg/sets/settings)
- Enter Schema.org mapping sets.


Notes
-----

Schema.org mapping set provides a quick way to set up and test different 
related Schema.org types while adjusting and refining your Schema.org types 
and property defaults.

Custom modules can also use the 'schemadotorg_mapping_set.manager' service 
to set up Schema.org types via code.
