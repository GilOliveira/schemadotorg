Table of contents
-----------------

* Introduction
* Features
* Notes


Introduction
------------

The **Schema.org Blueprint Demo Standard module** provides an opinionated demo of
the Schema.org Blueprints built on top of Drupal's standard profile.

**THIS MODULE SHOULD ONLY BE INSTALLED ON A PLAIN VANILLA STANDARD
INSTANCE OF DRUPAL**


Features
--------

- Installs contributed module settings. (/config/install)
- Uninstalls the Comment module and its associated fields.
- Rewrites contributed module settings. (/config/rewrite)
- Adds consumer image styles to the 'Default Consumer'.
- Adds shortcuts linking to key Schema.org Blueprints URLs.
- Creates path aliases for /api/*.
- Improves generated text value and summary to make it easier demo
  a lot of data.
- Default generated formatted text fields to use the 'full_html' text format.
-

Notes
-----

The **Schema.org Blueprint Demo Standard module** deliberately tries not to contain
a lot of exported configuration files. This makes it easier to maintain this
module's configuration by allowing the dependencies to make configuration
changes and improvements without impacting this demo.
