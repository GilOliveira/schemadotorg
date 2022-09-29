/**
 * @file
 * Schema.org form behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Schema.org form behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgFormSubmitOnce = {
    attach: function (context) {
      $(once('schemadotorg-submit-once', 'form.js-schemadotorg-submit-once', context))
        .each(function () {
          var $form = $(this);
          // Track which button is clicked.
          $form.find('.form-actions :submit').on('click', function () {
            $(this).addClass('js-schemadotorg-submit-clicked');
          });
          // Disable the submit button and disable the progress throbber.
          $form.on('submit', function () {
            $(this).find('.js-schemadotorg-submit-clicked')
              .prop('disabled', true)
              .after(Drupal.theme.ajaxProgressThrobber());
          });
        });
    }
  }

} (jQuery, Drupal, once));
