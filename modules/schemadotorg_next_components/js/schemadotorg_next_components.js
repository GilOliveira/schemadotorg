/**
 * @file
 * Schema.org Next.js components preview behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Schema.org Next.js components preview code prettier.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgNextComponentsPreviewCodePrettier = {
    attach: function attach(context) {
      $(once('.schemadotorg-next-components-preview-code', '.schemadotorg-next-components-preview-code', context))
        .each(function () {
          var text = prettier.format($(this).text(), {
            parser: 'typescript',
            plugins: prettierPlugins,
          });
          $(this).text(text);
        });
    }
  }

  /**
   * Schema.org Next.js components preview download.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgNextComponentsPreviewDownload = {
    attach: function attach(context) {
      $(once('schemadotorg-next-components-preview-download', '.js-schemadotorg-next-components-preview', context))
        .each(function () {
          var $container = $(this);
          var $component = $container.parent().find('.schemadotorg-next-components-preview-code');
          var $link = $container.find('.schemadotorg-next-components-preview-download-button');
          var fileName = $link.attr('href').replace('#', '');

          // @see https://ourcodeworld.com/articles/read/189/how-to-create-a-file-and-generate-a-download-with-javascript-in-the-browser-without-a-server
          $link.attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent($component.text()));
          $link.attr('download', fileName);
        });
    }
  }

  /**
   * Schema.org Next.js components preview copy.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgNextComponentsPreviewCopy = {
    attach: function attach(context) {
      $(once('schemadotorg-next-components-preview-copy', '.js-schemadotorg-next-components-preview', context))
        .each(function () {
          var $container = $(this);
          var $component = $container.parent().find('.schemadotorg-next-components-preview-code');
          var $button = $container.find(':submit, :button');
          var $message = $container.find('.schemadotorg-next-components-preview-copy-message');

          // Copy code from textarea to the clipboard.
          // @see https://stackoverflow.com/questions/47879184/document-execcommandcopy-not-working-on-chrome/47880284
          $button.on('click', function () {
            if (window.navigator.clipboard) {
              var text = $component.text();
              text = text.replaceAll(/<!--.+?-->\s*/sg, '');
              window.navigator.clipboard.writeText(text);
            }
            $message.show().delay(1500).fadeOut('slow');
            Drupal.announce(Drupal.t('Components copied to clipboard…'));
            return false;
          });
        });
    }
  }

} (jQuery, Drupal, once));
