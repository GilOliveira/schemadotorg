Schema.org Blueprints
---------------------

# Supported/recommended entity and field types

_The use-case for different entity and field types._

- **Content:** Used for any Schema.org types that should have a dedicated URL.

- **Paragraphs:** Used for complex Intangibles and StructuredData.

- **Block:** Used for Intangibles and Schema.org types that are embedded in node and layouts.

- **Taxonomy:** Used for vocabularies of DefinedTerm and CategoryCode sets.

- **User:** Used for when a dedicated Person type is needed for online community management.

- **Flexfield:** User for simple Intangibles and StructuredData. \[DEPRECATED\]

# Tips

- Schema.org Blueprints module should provide 80% of a site's base content architecture and the remaining 20% is custom configuration and code.

- The structured data examples from Schema.org should be considered the canonical reference for implementation guidelines.

- For SEO friendly structured data examples, Google should be a close second.

- Relationships should be a top down (a.k.a. parent to child) and not a child to parent relationships.
  - Use episodes instead of partOfSeason
  - Use 'has' instead of 'partOf'
  - Top down makes it easier to build JSON-LD which recurse downward.
  - Top down supports inline entity references with weighting.

# Demo

The Schema.org Blueprints Demo module and the Schema.org Blueprints Demo Standard Profile Setup module can be installed on a plain standard Drupal instance to provide an opinionated demonstration of the Schema.org Blueprints modules with an advanced Schema.org types and content authoring user experience.

## Mapping Sets

The Schema.org Blueprints Mapping Sets module provides a UI and Drush command to set up and test common sets of Schema.org types for different use case and industries.

Use cases include...

- **Common** (People, events, places, and organizations)
- **Blocks** (Teasers, quotes, and components)
- **How to** (Step-by-step guides)
- **Food** (Restaurants and menus)
- **Entertainment**	(Movies, TV, and podcasts)
- **Web**	(Webpage, FAQ, and slideshows)
- **Education**	(Schools and course)
- **Organization**	(Hours, ratings, services, job posting and businesses)
- **Medical organization**	(Physician, clinics, labs, hospitals, tests, and audience)
- **Medical information** (Conditions, symptoms, risks, causes, tests, procedures, and trials)

## Content authoring user experience

Schema.org Blueprints Demo Standard Profile Setup module requires and configures 
a suite of contributed modules that provide the below content authoring user
experiences with the Drupal administrative UI.

**Embedded content:** The ability to be embed media or content with the HTML editor. 

Embed media and content includes...

- Image with captions
- Media (Image, Video, Audio, etc...)
- Entities (Node, User, Block, etc...)

**Entity references:** The ability to reference other content or entities. 

Entity references patterns includes...

- Inline entity form
- Paragraphs

**Libraries/browse** The ability to create and browse a library of reusable media and entities.  
 
- Entity reference with content browsing
- Paragraphs library with content browsing
- Media library with content browsing

# Documentation

The Schema.org Reports module provides a browsable instance of [Schema.org](https://Schema.org).
@see /admin/reports/schemadotorg 

JSON:API documentation is provided by [ReDoc](https://redocly.github.io/redoc/) 
@see /api/documentation

# Schema.org JSON-LD Examples

- [NY Times](https://validator.schema.org/#url=nytimes.com)
- [Stack overflow](https://validator.schema.org/#url=https%3A%2F%2Fstackoverflow.com%2Fquestions%2F28687653%2Fschema-org-json-ld-where-to-place)
- [Amazon](https://validator.schema.org/#url=https%3A%2F%2Fwww.amazon.com%2FThe-Boys-Season-3%2Fdp%2FB09WV8HF7Q)
- [BBC](https://validator.schema.org/#url=bbc.co.uk)
  - [BBC: News](https://validator.schema.org/#url=https%3A%2F%2Fwww.bbc.com%2Fnews%2Fuk-61626176)
- [MayoClinic](https://validator.schema.org/#url=https%3A%2F%2Fwww.mayoclinic.org%2F)
  - [MayoClinic: Condition](https://validator.schema.org/#url=https%3A%2F%2Fwww.mayoclinic.org%2Fdiseases-conditions%2Facne%2Fdiagnosis-treatment%2Fdrc-20368048)
- [MSKCC](https://validator.schema.org/#url=mskcc.org)
- [GrubHub](https://validator.schema.org/#url=https%3A%2F%2Fwww.grubhub.com%2Frestaurant%2Fred-hot-ii-349-7th-ave-brooklyn%2F347523)
- [Gap](https://validator.schema.org/#url=https%3A%2F%2Fwww.gap.com%2Fbrowse%2Fproduct.do%3Fpid%3D406387012) 
