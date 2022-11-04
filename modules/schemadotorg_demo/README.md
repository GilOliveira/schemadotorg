Table of contents
-----------------

* Introduction
* Features
* Installation
* FAQ
* Notes


Introduction
------------

The **Schema.org Blueprint Demo module** provides an opinionated demo of the
Schema.org Blueprints built on top of Drupal's standard profile with default
content and translations.

This module provides a composer.libraries.json file that allows sites to
download and install contributed modules with patches which are used to set up 
a full demo of the Schema.org Blueprints module within a progressively decoupled
Drupal instance.

**THIS MODULE SHOULD ONLY BE INSTALLED ON A PLAIN VANILLA STANDARD
INSTANCE OF DRUPAL**


Features
--------

**Administration**

A radically new administration interface using the Gin admin theme

- Modern administrative theme
- Dedicated and branded login page
- Customizable administrative toolbar
- Customizable administrative dashboards

**Content authoring**

Examples of Drupal's content authoring best practices and patterns

- Media support
- Media library
- Paragraph library
- Embedded content
- Content browsing
- Inline entity forms
- Automatic entity labels
- Content cloning
- Drag-n-drop file uploads
- Link management
- PDF generation
- URL generation
- Decoupling using Next.js

**Multilingual support**

- Language switching

**Default content**

- Example recipes in English and Spanish copied from the
  [Umami demo install profile](https://www.drupal.org/docs/umami-drupal-demonstration-installation-profile).


Sub-modules
-----------

- **[Schema.org Blueprints Demo Standard Profile Setup](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_demo/modules/schemadotorg_demo_standard)**    
  Provides an opinionated demo of the Schema.org Blueprints built on top of Drupal's standard profile.

- **[Schema.org Blueprints Demo Standard Profile Translation](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_demo/modules/schemadotorg_demo_standardard_translation)**    
  Provides an opinionated translation of the Schema.org Blueprints built on top of Drupal's standard profile.

- **[Schema.org Blueprints Demo Standard Profile Admin](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_demo/modules/schemadotorg_demo_standard_admin)**    
  Provides admin UI enhancements for the Schema.org Blueprints demo built on top of Drupal's standard profile.

- **[Schema.org Blueprints Default Content](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_demo/modules/schemadotorg_demo_default_content)**    
  Provides default Schema.org types and mappings with default content.

- **[Schema.org Blueprints Umami Demo Content](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_demo/modules/schemadotorg_demo_umami_content)**    
  Imports the content for the Umami demo with Schema.org Blueprints.

- **[Schema.org Blueprints Demo Next.js](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_demo/modules/schemadotorg_demo_next)**    
  Provides a demo Schema.org Blueprints used Next.js for Drupal.


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
    - From the root run one of the below scripts.
      - `web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard`
      - `web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard_next`
      - `web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard_translation`
      - `web/modules/contrib/schemadotorg_demo/scripts/schemadotorg_demo.sh install_demo_standard_translation_umami`

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

**Why does this module only contain a composer.libraries.json file and \*.info.yml file?**

The composer.libraries.json file provides a quick way to include or cut-n-paste
the demo's dependencies. The \*.info.yml file provides a single module that is
used to enable the installation of a complete demo while allowing individual
parts of the demo to be maintained as smaller modules.


Notes
-----

The **Schema.org Blueprints Demo module** and sub-modules may be moved to a
dedicated project on Drupal.org.

The below patch breaks the Next.js module's JSON:API support.

    "patches": {
      "drupal/core": {
        "Issue #3100732: Allow specifying `meta` data on JSON:API objects": "https://www.drupal.org/files/issues/2022-05-15/3100732-33.patch"
      },
    }
