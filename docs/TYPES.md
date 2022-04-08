Types
-----

# General

- For high-level types, which are inherited from, we want to keep the
  default properties as simple as possible.
- For specific and important types, include Recipe, we should be specific
  as needed with the default properties.

About entity types
- Paragraphs should be used for Intangibles.
- Media is used for MediaObjects.
- Nodes are used for everything else.

--------------------------------------------------------------------------------

# Schema.org types

Person
- Includes common user account and meta data fields.

Place
- Only includes address, contact, and image.

Organization
- Only includes address, contact, and parent organization.

Event
- Includes time, place, type, organization, audience, and status.

CreativeWork
- Includes dates and descriptions
- Keywords should be handled via meta tags

- Todo
- teaser
- slideshow
- lists
- forms
- toc
- faq
- timeline

FAQPage
- https://developers.google.com/search/docs/advanced/structured-data/faqpage
- mainEntity: Question
- https://schema.org/FAQPage
- https://schema.org/Question
- https://schema.org/acceptedAnswer
- https://schema.org/Answer

HowTo
- https://developers.google.com/search/docs/advanced/structured-data/how-to

