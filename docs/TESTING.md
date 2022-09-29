Schema.org Blueprints: Testing
------------------------------

# Manual JavaScript tests

_The below manual JavaScript tests should be moved to automated tests._

**schemadotorg.autocomplete.js**

@see /admin/reports/schemadotorg

- Check that selected type in form redirects to the type.

**schemadotorg.details.js**

@see /node/add/person

- Check on node via the Schema.org details widget's hide/close state is saved.

**schemadotorg.dialog.js**

@see /admin/structure/types/schemadotorg?type=Person

- Check that links to Schema.org open a modal dialog.

**schemadotorg.form.js**

@see /admin/config/search/schemadotorg/sets/common/setup

- Check that the form is only be submitted once with progress throbber

**schemadotorg_ui.js**

@see /admin/structure/types/schemadotorg?type=Person

- Check that the 'Hide/Show unmapped' link toggles the displayed properties.

- Check that the 'Filter by Schema.org property' filters the displayed properties.

- Check that the 'Add new field' summary is updated as the new field is configured.

**schemadotorg_jsonld_preview.js**

@see /node/add/person

- Check that Schema.org JSON-LD can be copied-n-pasted into the Schema Markup Validator.

**schemadotorg_next_components.js**

@see /node/add/person

- Check that Next.js** component can be download and copied-n-pasted
