Table of contents
-----------------

* Introduction
* Features
* Configuration

Introduction
------------

The **Schema.org Blueprints Next.js Components module** generates and previews 
Next.js React components to assist with the integration of 
Schema.org Blueprints with Next.js


Features
--------

- Preview Next.js components with download or copy components.


Configuration
-------------

- Configure 'Schema.org Blueprints Next.js Components Preview' permission.
  (/admin/people/permissions/module/schemadotorg_next_components)


References
----------

Tailwind

- https://flowbite.com/tools/tailwind-cheat-sheet/
- https://flowbite.com/docs/getting-started/introduction/
- https://tailwind-elements.com/docs/standard/components/alerts/


Todo
----

General

- Add Next.js column to /admin/config/search/schemadotorg
  - Create 'schemadotorg_next_components.entity'
  - /admin/config/search/schemadotorg/media.audio/next
  - Details view should include summary and component.
- Export other entity types included media and paragraphs
- Improve import handling for components. Maybe pass $context array. 
- Apply Prettier to generated files.

Components/features

- Date formatting
- Text formatting
- \<Image\> support
- \<Link\> support
- Meta tags
- JSON-LD
- Media images
- Entity reference links
- \<DynamicEntity entity={entity} /\> - <https://nextjs.org/docs/advanced-features/dynamic-import> 
