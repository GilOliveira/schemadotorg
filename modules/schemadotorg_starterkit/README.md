Table of contents
-----------------

* Introduction
* Features
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
