/**
 * @file
 * Schema.org JSON-LD behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Schema.org JSON-LD copy.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgJsonLdCopy = {
    attach: function attach(context) {
      $(context).find('.js-schemadotorg-jsonld').once('schemadotorg-jsonld-copy').each(function () {
        var $container = $(this);
        var $pre = $container.find('pre');
        var $button = $container.find(':submit, :button');
        var $message = $container.find('.schemadotorg-jsonld-copy-message');
        // Copy code from textarea to the clipboard.
        // @see https://stackoverflow.com/questions/47879184/document-execcommandcopy-not-working-on-chrome/47880284
        $button.on('click', function () {
          if (window.navigator.clipboard) {
            window.navigator.clipboard.writeText($pre.html());
          }
          $message.show().delay(1500).fadeOut('slow');
          Drupal.announce(Drupal.t('JSON-LD copied to clipboard…'));
          return false;
        });
      });
    }
  }

} (jQuery, Drupal, once));
