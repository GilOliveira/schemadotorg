/**
 * @file
 * Schema.org dialog behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Open Schema.org type and property report links in a modal dialog.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgDialog = {
    attach: function (context) {
      $('a[href*="/admin/reports/schemadotorg/"]', context)
        .once('schemadotorg-dialog').each(function () {
          Drupal.ajax({
            progress: {type: 'fullscreen'},
            url: $(this).attr('href'),
            event: 'click',
            dialogType: 'modal',
            dialog: {width: '90%'},
            element: this,
          });
        });
    }
  }

} (jQuery, Drupal));
