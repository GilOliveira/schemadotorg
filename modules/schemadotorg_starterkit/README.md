Table of contents
-----------------

* Introduction
* Features
* Notes
* Usage
* Todo


Introduction
------------

The **Schema.org Blueprints Starterkit (API)** module provides an API for 
starter kits to create Schema.org types.


Features
--------

- Allows a starter kit or module to declare what Schema.org types are require
  preinstallation.
- Post module installation, re-imports optional configuration and rewrites 
  configuration via via the config_rewrite.module, this allows hook_install 
  to create additional configuration settings.


Notes
-----

## Starterkit module phases

### Pre-install

- Rewrites any schemadotorg configuration.   
  _This allows starter kits to change how a Schema.org will be created._
- Creates Schema.org types via *.schemadotorg_starterkit.yml
- Rewrites existing and newly created configuration.
- Imports starterkits configuration.  

### Install

- Generates content
- Programmatic tweaks

  
Usage
-----

Create a `MODULE_NAME.schemadotorg_starterkit.yml` file

Inside the `MODULE_NAME.schemadotorg_starterkit.yml` file declare what 
Schema.org types and properties should be created preinstallation.

```
types:
  'node:Event':
    properties:
      eventSchedule:
        label: When
        description: 'Enter when is the event occuring.'
      image: false
      eventStatus: false
      location: false
      organizer: false
      performer: false
```


TODO
----

- Determine what other pre-installation configuration and behaviors 
  starter kits require.

- Possibly create an admin page, similar to the Features UI, that tracks 
  which starter kits are available and installed.  

- Potential starter kits
  - events
  - people
  - places
  - organizations
  - recipes
