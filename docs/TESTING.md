Testing
-------

# Manual Tests

User
- http://localhost/so/admin/config/people/accounts/schemedotorg
- `drush schemadotorg:create-type user Person`

Media
- http://localhost/so/admin/structure/media
- `drush schemadotorg:create-type media AudioObject DataDownload ImageObject VideoObject`

Paragraphs
- http://localhost/so/admin/structure/paragraphs_type/schemadotorg
- `drush schemadotorg:create-type paragraph ContactPoint PostalAddress`

Node
- http://localhost/so/admin/structure/types/schemadotorg
- `drush schemadotorg:create-type node Person Organization Place Event CreativeWork`

# Automated Tests

Entity

SchemaDotOrgMapping
- \Drupal\KernelTests\Core\Entity\EntityDisplayFormBaseTest
- \Drupal\KernelTests\Core\Entity\EntityDisplayRepositoryTest

Services

- SchemaDotOrgInstaller.php
- SchemaDotOrgBuilder.php
- SchemaDotOrgManager.php
- SchemaDotOrgNames.php

Report

- Filter form.
- Confirm types.
- Confirm properties.
- Confirm things.
- Confirm intangibles.
- Confirm enumerations.
- Confirm data types.
- Confirm names.
- Confirm warning.

UI
- FieldUIRouteTest.php
