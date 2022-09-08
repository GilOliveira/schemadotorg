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

TypeScript

- https://github.com/typescript-cheatsheets/react
- https://www.typescriptlang.org/cheatsheets

Tailwind

- https://flowbite.com/tools/tailwind-cheat-sheet/
- https://flowbite.com/docs/getting-started/introduction/
- https://tailwind-elements.com/docs/standard/components/alerts/

PHPStorm

- https://www.jetbrains.com/help/phpstorm/tailwind-css.html


Todo
----

General

- Add Next.js column to /admin/config/search/schemadotorg
  - Create 'schemadotorg_next_components.entity'
  - /admin/config/search/schemadotorg/media.audio/next
  - Details view should include summary and component.
- Export other entity types included paragraphs

Schema.org types

- HowTo - has nested paragraphs

Components/features

- JSON-LD
- Address 
- Time range
- Date range
- \<DynamicEntity entity={entity} /\> - <https://nextjs.org/docs/advanced-features/dynamic-import> 
