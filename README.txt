Schema.org
----------


Todo


- schemadotorg_things vocabulary.
- schemadotorg_intangibles vocabulary.
- schemadotorg_enumerations vocabulary.
- schemadotorg_structured_values vocabulary.

- Custom labels

- Mark as experimental.


--------------------------------------------------------------------------------

- Add help to types and properties reports.
  - Note version.
  - Link to source CSV.

- Add reports for targeted entity types
  - node
  - paragraphs
  - terms

- Research UI hooks
  - Add Schema.org type
  - Add Schema.org properties

- Use Entity Builder class to add helper
  @see https://www.drupal.org/node/3191609

Tests

Installer


Utility

- SchemaDotOrgStringHelper
  - Possibly use all labels in tests.

Report
- Confirm types.
- Confirm properties.
- Confirm things.
- Confirm intangibles.
- Confirm enumerations.
- Confirm data types.
- Confirm names.
- Confirm warning.

TBD

- Should we prefix all schema field with schema_* ?


- Should machine name be generates during the import?
  - machine_name
  - SchemaDotOrgStringHelper::toMachineName($text)

- How to handle field names with 32 characters limit?
  - For multiple word abbreviate by taking first 3 characters.
  - actionAccessibilityRequirement => act_acc_req
  - additionalNumberOfGuests => add_num_gue
  - annualPercentageRate => ann_per_rat

- How to handle translations for imported data?
