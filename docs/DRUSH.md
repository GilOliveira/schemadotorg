Drush
-----

# Available commands

```
drush list --filter=schemadotorg

Available commands:
schemadotorg:
schemadotorg:create-type (socr)   Create Schema.org types.
schemadotorg:delete-type (sode)   Delete Schema.org type.
schemadotorg:update-schema (soup) Update Schema.org data.
```

# Usage

## Import features

```
drush features:import -y schemadotorg
drush features:import -y schemadotorg_jsonld
drush features:import -y schemadotorg_report
```

## Setup Schema.org types with example content.

```
# Generate Schema.org types.
drush schemadotorg:create-type -y paragraph:ContactPoint paragraph:PostalAddress
drush schemadotorg:create-type -y media:AudioObject media:DataDownload media:ImageObject media:VideoObject
drush schemadotorg:create-type -y user:Person
drush schemadotorg:create-type -y node:Person node:Organization node:Place node:Event

# Generate content (skip menu_link fields which throw fatal errors).
drush devel-generate:users --kill 50
drush devel-generate:media --kill 50
drush devel-generate:content --kill --skip-fields=menu_link\
 --add-type-label\
 --bundles=person,organization,place,event 50
```

## Teardown Schema.org types with example content.

```
# Delete content.
drush devel-generate:users --kill 0
drush devel-generate:media --kill 0
drush devel-generate:content --kill 0

# Delete Schema.org types.
drush schemadotorg:delete-type -y --delete-fields user:Person
drush schemadotorg:delete-type -y --delete-fields media:AudioObject media:DataDownload media:ImageObject media:VideoObject
drush schemadotorg:delete-type -y --delete-entity paragraph:ContactPoint paragraph:PostalAddress
drush schemadotorg:delete-type -y --delete-entity node:Person node:Organization node:Place node:Event
```

## Demo

```
drush schemadotorg:create-type -y paragraph:ContactPoint
drush schemadotorg:create-type -y media:ImageObject
drush schemadotorg:create-type -y node:Place node:Organization node:Person node:Event
drush schemadotorg:create-type -y node:Article node:WebPage
drush devel-generate:users --kill 50
drush devel-generate:content --kill --add-type-label  50
```
