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

  /**
   * Programmatically open a Schema.org type or property in a dialog.
   *
   * @param {string} url
   *   Webform URL.
   */
  Drupal.schemaDotOrgOpenDialog = function (url) {
    if (url.indexOf('/admin/reports/schemadotorg/') === -1) {
      window.location.href = url;
    }
    else {
      // Create a div with link but don't attach it to the page.
      var $div = $('<div><a href="' + url + '"></a></div>');
      // Init the dialog behavior.
      Drupal.behaviors.schemaDotOrgDialog.attach($div);
      // Trigger the link.
      $div.find('a').trigger('click');
    }

  };

} (jQuery, Drupal));
