/**
 * @file
 * Schema.org autocomplete behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Schema.org filter autocomplete handler.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgAutocomplete = {
    attach: function (context) {
      $(once('schemadotorg-autocomplete', 'input.schemadotorg-autocomplete', context))
        .each(function () {
          // If input value is an autocomplete match, reset the input to its
          // default value.
          if (/\(([^)]+)\)$/.test(this.value)) {
            this.value = this.defaultValue;
          }

          // jQuery UI autocomplete submit onclick result.
          // @see http://stackoverflow.com/questions/5366068/jquery-ui-autocomplete-submit-onclick-result
          $(this).bind('autocompleteselect', function (event, ui) {
            if (ui.item) {
              var action = $(this).data('schemadotorg-autocomplete-action');
              if (action) {
                var url = action + '/' + ui.item.value;
                if (Drupal.schemaDotOrgOpenDialog && $(this).closest('.ui-dialog').length) {
                  Drupal.schemaDotOrgOpenDialog(url);
                }
                else {
                  top.location = url;
                }
              }
              else {
                $(this).val(ui.item.value);
                $(this.form).trigger('submit');
              }
            }
          });
        });
    }
  };

} (jQuery, Drupal, once));
