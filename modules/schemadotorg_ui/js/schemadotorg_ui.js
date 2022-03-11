/**
 * @file
 * Schema.org UI behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Schema.org UI properties behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertyStatus = {
    attach: function (context) {
      $('table.schemadotorg-ui-properties select[name$="[field][name]"]', context).once('schemadotorg-ui-properties')
        .change(function () {
          var $select = $(this);
          var value = $select.val();
          var defaultValue = '';
          $select.find('option').each(function (index, option) {
            if (option.defaultSelected) {
              defaultValue = option.value;
              return;
            }
          });

          var $tr = $select.parents('tr');
          $tr.removeClass('color-success').removeClass('color-warning');
          if (value) {
            $tr.addClass( (value !== defaultValue) ? 'color-warning' : 'color-success')
          }
        })
    }

  };

} (jQuery, Drupal));
