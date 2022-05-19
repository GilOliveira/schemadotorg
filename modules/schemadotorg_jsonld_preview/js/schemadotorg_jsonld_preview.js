/**
 * @file
 * Schema.org JSON-LD preview behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  // Determine if local storage exists and is enabled.
  // This approach is copied from Modernizr.
  // @see https://github.com/Modernizr/Modernizr/blob/c56fb8b09515f629806ca44742932902ac145302/modernizr.js#L696-731
  var hasLocalStorage = (function () {
    try {
      localStorage.setItem('schemadotorg_jsonld', 'schemadotorg_jsonld');
      localStorage.removeItem('schemadotorg_jsonld');
      return true;
    }
    catch (e) {
      return false;
    }
  }());

  /**
   * Tracks Schema.org JSON-LD preview details open/close state.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgJsonLdPreviewState = {
    attach: function attach(context) {
      if (!hasLocalStorage) {
        return;
      }

      $('details', context)
        .once('schemadotorg-jsonld-preview-state')
        .each( function () {
          var $details = $(this);
          $details.find('summary').on('click', function () {
            var open = ($details.attr('open') !== 'open') ? '1' : '0';
            localStorage.setItem('schemadotorg_jsonld_details', open);
          });

          var open = localStorage.getItem('schemadotorg_jsonld_details');
          if (open === '1') {
            $details.attr('open', 'open');
          }
        });
    }
  }

  /**
   * Schema.org JSON-LD preview copy.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgJsonLdPreviewCopy = {
    attach: function attach(context) {
      $(context).find('.js-schemadotorg-jsonld-preview').once('schemadotorg-jsonld-preview-copy').each(function () {
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
