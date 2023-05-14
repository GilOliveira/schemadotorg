Table of contents
-----------------

* Introduction
* Features
* Requirements


Introduction
------------

The **Schema.org Blueprints Geolocation Field** allows a geolocation field to 
be used to create https://schema.org/GeoCoordinates.


Features
--------

- During installation, alters http://schema.org/Place to use the
  [geo](https://schema.org/geo) property.

- Alters Schema.org JSON-LD for https://schema.org/geo to use the
  https://schema.org/GeoCoordinates type.


Requirements
------------

**[Geolocation Field](https://www.drupal.org/project/geolocation)**    
Provides a field type to store geographical locations as pairs of latitude 
and longitude (lan,lng) as well as the necessary integration to display those
locations through views, fields and using a number of different map providers.
