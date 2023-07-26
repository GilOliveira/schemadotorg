Table of contents
-----------------

* Introduction
* Features
* Configuration
* Requirements
* References


Introduction
------------

The **Schema.org Blueprints Diagramn** module provides
diagrams for Schema.org relationships.


Features
--------

- Provides a Schema.org Organization diagram using a flow chart 
  generated using Mermaid.js.


Configuration
-------------

Permissions

- Configure 'View Schema.org Diagram' permission.  
  (/admin/people/permissions/module/schemadotorg_diagram)

Settings

- Go to the Schema.org general configuration page.  
  (/admin/config/search/schemadotorg/settings/general)
- Go to the 'Diagram settings' details.
- Enter Schema.org diagrams title, parent, and child Schema.org properties.


Concepts
--------

## Schema.org types and properties

https://schema.org/Person
- https://schema.org/memberOf
- https://schema.org/worksFor

https://schema.org/Organization
- https://schema.org/subOrganizations
- https://schema.org/parentOrganization
- https://schema.org/member
- https://schema.org/employee

## Corresponding Entity References

- https://schema.org/subOrganization ↔ https://schema.org/parentOrganization
- https://schema.org/memberOf ↔ https://schema.org/member
- https://schema.org/worksFor ↔ https://schema.org/employee


Requirements
------------

## Contribute modules (Optional)

- **[Content Model Documentation](https://www.drupal.org/project/content_model_documentation)**  
  Adds admin displays for the site architecture and history.

## JavaScript Libraries

- **[Mermaid](https://mermaid.js.org)**  
  Mermaid lets you create diagrams and visualizations using text and code.


References
----------

- https://en.wikipedia.org/wiki/Hierarchical_organization

