/**
 * @file
 * Schema.org JSON-LD details behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  // Determine if local storage exists and is enabled.
  // This approach is copied from Modernizr.
  // @see https://github.com/Modernizr/Modernizr/blob/c56fb8b09515f629806ca44742932902ac145302/modernizr.js#L696-731
  var hasLocalStorage = (function () {
    try {
      localStorage.setItem('schemadotorg_details', 'schemadotorg_details');
      localStorage.removeItem('schemadotorg_details');
      return true;
    }
    catch (e) {
      return false;
    }
  }());

  /**
   * Tracks Schema.org JSON-LD details open/close state.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgDetailsState = {
    attach: function attach(context) {
      if (!hasLocalStorage) {
        return;
      }

      $('details[data-schemadotorg-details-state]', context)
        .once('schemadotorg-details-state')
        .each( function () {
          var $details = $(this);
          var key = $details.attr('data-schemadotorg-details-key');
          $details.find('summary').on('click', function () {
            var open = ($details.attr('open') !== 'open') ? '1' : '0';
            localStorage.setItem(key, open);
          });

          var open = localStorage.getItem(key);
          if (open === '1') {
            $details.attr('open', 'open');
          }
        });
    }
  }

} (jQuery, Drupal, once));
