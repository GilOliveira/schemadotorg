Table of contents
-----------------

* Introduction
* Features
* Notes


Introduction
------------

The Schema.org Blueprint Demo Standard module provides an opinionated demo of 
the Schema.org Blueprints built on top of Drupal's standard profile.

**THIS MODULE SHOULD ONLY BE INSTALLED ON A PLAIN VANILLA STANDARD
INSTANCE OF DRUPAL**


Features
--------

- Installs contributed module settings. (@see /config/install)
- Rewrites contributed module settings. (@see /config/rewrite)
- Enables the Gin admin theme.
- Enables the Gin related modules.
- Configures the Gin admin theme.
- Uninstalls unused themes.
- Adds consumer image styles to the 'Default Consumer'.
- Adds shortcuts lining to key Schema.org Blueprints URLs.
- Creates path aliases for /api/*.


Notes
-----

The Schema.org Blueprint Demo Standard module deliberately tries not to contain
a lot of exported configuration files. This make it easier to maintain this 
module's configuration by allowing the dependencies to make configuration 
changes and improvements as needed without impacting this demo.
