/**
 * @file
 * Schema.org autocomplete behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Schema.org filter autocomplete handler.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgAutocomplete = {
    attach: function (context) {
      $('input.schemadotorg-autocomplete', context).once('schemadotorg-autocomplete')
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
              $(this).val(ui.item.value);
              $(this.form).trigger('submit');
            }
          });
        });
    }

  };

} (jQuery, Drupal));
