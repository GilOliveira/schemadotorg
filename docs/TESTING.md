Testing
-------

SchemaDotOrgInstaller.php
SchemaDotOrgNames.php
SchemaDotOrgEntityTypeBuilder.php
SchemaDotOrgEntityTypeManager.php
SchemaDotOrgNames.php
SchemaDotOrgSchemaTypeBuilder.php
SchemaDotOrgSchemaTypeManager.php

SchemaDotOrgMappingEntity


# Steps

- PHPUnit
- PHPUnit in Drupal
- Tests in Core

# References

PHPUnit
- [PHPUnit in Drupal](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal)
- [Types of tests](https://www.drupal.org/docs/automated-testing/types-of-tests)

Browser
- [PHPUnit Browser test tutorial](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal/phpunit-browser-test-tutorial)

JavaScript
- [PHPUnit JavaScript test writing tutorial](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal/phpunit-javascript-test-writing-tutorial)

MockBuilder

Prophesize

Running tests
- https://www.drupal.org/docs/automated-testing/phpunit-in-drupal

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
