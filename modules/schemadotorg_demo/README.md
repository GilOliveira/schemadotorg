Table of contents
-----------------

* Introduction
* Features
* Installation
* FAQ
* Notes


Introduction
------------

The Schema.org Blueprint Demo module provides an opinionated demo of the 
Schema.org Blueprints built on top of Drupal's standard profile with default 
content and translations.

This module provides a composer.libraries.json file that allows sites to 
download and install contributed modules which are used to set up a full demo 
of the Schema.org Blueprints module within a progressively decoupled 
Drupal instance.  

**THIS MODULE SHOULD ONLY BE INSTALLED ON A PLAIN VANILLA STANDARD
INSTANCE OF DRUPAL**


Features
--------

A radically new administration interface using the Gin admin theme

- Dedicated and branded login page 
- Customizable administrative dashboards

Examples of Drupal's content authoring best practices and patterns

- Media support
- Embedded content
- Content browsing
- Inline entity editing
- Content cloning
- Drag-n-drop file uploads
- Link management

Multilingual support

- Language switching

Default content

- Example recipes in English and Spanish copied from Umami demo install profile.


Installation
------------

- [Use Composer to Install Drupal and Manage Dependencies](https://www.drupal.org/docs/develop/using-composer/manage-dependencies)
- [Install the Schema.org Blueprints view composer](https://www.drupal.org/project/schemadotorg/releases/1.0.x-dev)
  - Run `composer require 'drupal/schemadotorg:1.0.x-dev@dev'`
- [Install the Composer Merge Plugin](https://github.com/wikimedia/composer-merge-plugin)
  - Run `composer require wikimedia/composer-merge-plugin`
- Add the Schema.org Blueprints Demo's composer.libraries.json to composer.json
  - Update the root `composer.json` file to include `schemadotorg_demo/composer.libraries.json`
  - See the below example of the composer.libraries.json added to composer.json
- Install the Schema.org Blueprints Demo
  - **OPTION 1**
    - Install plain vanilla standard instance of Drupal
      - Run `drush site-install`
    - Install the Schema.org Blueprints Demo
      - Run `drush en schemadotorg_demo`
  - **OPTIONS 2**
    - From the root run the below scripts.
      - Run `web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh demo`
      - Run `web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh translate`

Example of the Schema.org Blueprints Demo's composer.libraries.json added to composer.json

    {
        "extra": {
            "merge-plugin": {
                "include": [
                    "web/modules/contribute/schemadotorg/modules/schemadotorg_demo/composer.libraries.json",
                ],
                "merge-extra": true
            }
        }
    }


FAQ
---

**Why does this module only contain a composer.libraries.json file and *.info.yml file?**

The composer.libraries.json file provides a quick way to include or cut-n-paste
the demo's dependencies. The *.info.yml file provides a single module that is 
used to enable the installation of a complete demo while allowing individual 
parts of the demo to be maintained as smaller modules.


Notes
-----

The Schema.org Blueprints Demo module and sub-modules may be moved to a 
dedicated project on Drupal.org.
