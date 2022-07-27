Schema.org Blueprints: Demo
---------------------------

* Introduction
* Installation


INTRODUCTION
------------

The Schema.org Blueprints Demo module provides an opinionated demo of the
Schema.org Blueprints module built on top of Drupal's standard profile with
default content from the Umami demo installation profile.

**THIS DEMO MODULE SHOULD ONLY BE INSTALLED ON A PLAIN VANILLA STANDARD INSTANCE OF DRUPAL**


INSTALLATION
------------

- [Use Composer to Install Drupal and Manage Dependencies](https://www.drupal.org/docs/develop/using-composer/manage-dependencies)
- [Install the Schema.org Blueprints view composer](https://www.drupal.org/project/schemadotorg/releases/1.0.x-dev)
  - Run `composer require 'drupal/schemadotorg:1.0.x-dev@dev'`
- [Install the Composer Merge Plugin](https://github.com/wikimedia/composer-merge-plugin)
  - Run `composer require wikimedia/composer-merge-plugin`
- Add the Schema.org Blueprints Demo's composer.libraries.json to composer.json
  - Update the root `composer.json` file to include `schemadotorg_demo/composer.libraries.json`
  - See example below.
- Install the Schema.org Blueprints Demo
  - OPTION 1
    - Install plain vanilla standard instance of Drupal
      - Run `drush site-install`
    - Install the Schema.org Blueprints Demo
      - Run `drush en schemadotorg_demo`
  - OPTIONS 2
    - From the root run the below scripts.
      - Run `web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh demo`
      - Run `web/modules/contrib/schemadotorg/scripts/schemadotorg-demo.sh translate`

Example of the Schema.org Blueprints Demo's composer.libraries.json added to composer.json

```
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
```
