/**
 * @file
 * Schema.org JSON-LD preview behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Schema.org JSON-LD preview copy.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgJsonLdPreviewCopy = {
    attach: function attach(context) {
      $(once('schemadotorg-jsonld-preview-copy', '.js-schemadotorg-jsonld-preview', context))
        .each(function () {
        var $container = $(this);
        var $input = $container.find('input:hidden');
        var $button = $container.find(':submit, :button');
        var $message = $container.find('.schemadotorg-jsonld-preview-copy-message');

        // Copy code from textarea to the clipboard.
        // @see https://stackoverflow.com/questions/47879184/document-execcommandcopy-not-working-on-chrome/47880284
        $button.on('click', function () {
          if (window.navigator.clipboard) {
            window.navigator.clipboard.writeText($input.val());
          }
          $message.show().delay(1500).fadeOut('slow');
          Drupal.announce(Drupal.t('JSON-LD copied to clipboard…'));
          return false;
        });
      });
    }
  }

} (jQuery, Drupal, once));
