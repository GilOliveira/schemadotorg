/* eslint-disable strict, no-undef, no-use-before-define */

/**
 * @file
 * Schema.org Next.js components preview behaviors.
 */

"use strict";

((Drupal, once) => {
  /**
   * Schema.org Next.js components preview code prettier.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgNextComponentsPreviewCodePrettier = {
    attach: function attach(context) {
      once('.schemadotorg-next-components-preview-code', '.schemadotorg-next-components-preview-code', context)
        .forEach((element) => {
          element.innerText = prettier.format(element.innerText, {
            parser: 'typescript',
            plugins: prettierPlugins,
          });
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
      once('schemadotorg-next-components-preview-download', '.js-schemadotorg-next-components-preview', context)
        .forEach((container) => {
          const component = container.parentNode.querySelector('.schemadotorg-next-components-preview-code');
          const link = container.querySelector('.schemadotorg-next-components-preview-download-button');
          const fileName = link.getAttribute('href')
            .replace('#', '');

          // @see https://ourcodeworld.com/articles/read/189/how-to-create-a-file-and-generate-a-download-with-javascript-in-the-browser-without-a-server
          const encodedComponent = encodeURIComponent(component.innerHTML);
          link.setAttribute('href', `data:text/plain;charset=utf-8,${encodedComponent}`);
          link.setAttribute('download', fileName);
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
      once('schemadotorg-next-components-preview-copy', '.js-schemadotorg-next-components-preview', context)
        .forEach((container) => {
          const component = container.parentNode.querySelector('.schemadotorg-next-components-preview-code');
          const message = container.querySelector('.schemadotorg-next-components-preview-copy-message');
          const button = container.querySelector('input[type="submit"], button');

          message.addEventListener('transitionend', hideMessage);

          button.addEventListener('click', event => {
            // Copy code from textarea to the clipboard.
            // @see https://stackoverflow.com/questions/47879184/document-execcommandcopy-not-working-on-chrome/47880284
            if (window.navigator.clipboard) {
              let text = component.innerText;
              text = text.replaceAll(/<!--.+?-->\s*/sg, '');
              window.navigator.clipboard.writeText(text);
            }

            showMessage();

            Drupal.announce(Drupal.t('Components copied to clipboard…'));

            event.preventDefault();
          });

          // Show/hide message handling.
          // @see https://stackoverflow.com/questions/29017379/how-to-make-fadeout-effect-with-pure-javascript
          function showMessage() {
            message.style.display = 'inline-block'
            setTimeout(() => {message.style.opacity = '0'}, 1500);
          }

          function hideMessage() {
            message.style.display = 'none'
            message.style.opacity = '1';
          }
        });
    }
  }
})(Drupal, once);
