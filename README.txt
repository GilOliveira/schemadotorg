Schema.org
----------


Todo
- Names
  - All
  - Types
  - Properties/Fields

_ schemadotorg_report.module

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

Tests

Installation
- Confirm types table.
- Confirm properties table.
- Confirm types taxonomy vocabulary.

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
