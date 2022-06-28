Schema.org Blueprints
---------------------

# Supported/recommended entity and field types

_The use-case for different entity and field types._

- **Content:** Used for any Schema.org types that should have a dedicated URL.

- **Paragraphs:** Used for complex Intangibles and StructuredData.

- **Block:** Used for Intangibles and Schema.org types that are embedded in node and layouts.

- **Taxonomy:** Used for vocabularies of DefinedTerm and CategoryCode sets.

- **User:** Used for when a dedicated Person type is needed for online community management.

- **Flexfield:** User for simple Intangibles and StructuredData.

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

The Schema.org Blueprints Demo module and the Schema.org Blueprints Standard Profile Setup module can be installed on a plain standard Drupal instance to provide an opinionated demonstration of the Schema.org Blueprints modules with an advanced Schema.org types and content authoring user experience.

## Demo Schema.org types

The Schema.org Blueprints Demo module provides a Drush command with configurable settings to set up and test common sets of Schema.org types for different use case and industries.

Use cases include...

- Common (People, events, places, and organizations)
- Blocks (Teasers, quotes, and components)
- How to (Step-by-step guides)
- Food (Restaurants and menus)
- Entertainment	(Movies, TV, and podcasts)
- Web	(Webpage, FAQ, and slideshows)
- Education	(Schools and course)
- Organization	(Hours, ratings, services, job posting and businesses)
- Medical organization	(Physician, clinics, labs, hospitals, tests, and audience)
- Medical information (Conditions, symptoms, risks, causes, tests, procedures, and trials)

## Content authoring user experience

Schema.org Blueprints Standard Profile Setup module requires and configures 
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
 
- Entity reference with browse
- Paragraphs library with browse
- Media library with browse

# Documentation

The Schema.org Reports module provides a browsable instance of [Schema.org](https://Schema.org).
@see /admin/reports/schemadotorg 

JSON:API documentation is provides by [ReDoc](https://redocly.github.io/redoc/) 
@see /api/documentation
