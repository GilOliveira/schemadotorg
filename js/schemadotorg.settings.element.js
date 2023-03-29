
/**
 * @file
 * Schema.org settings element behaviors.
 */

"use strict";

((Drupal, once) => {

  /**
   * CodeMirror options.
   *
   * @type {object}
   */
  const options = {
    mode: 'yaml',
    lineNumbers: true,
    extraKeys: {
      // Setting for using spaces instead of tabs.
      // @see https://github.com/codemirror/CodeMirror/issues/988
      Tab: function (cm) {
        const spaces = Array(cm.getOption('indentUnit') + 1).join(' ');
        cm.replaceSelection(spaces, 'end', '+element');
      },
    },
  };

  /**
   * Schema.org settings element YAML behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgSettingsElementYaml = {
    attach: function attach(context) {
      if (!window.CodeMirror) {
        return;
      }

      once('schemadotorg-settings-element-yaml', '.schemadotorg-settings-element-yaml', context)
        .forEach((element) => {
          // Track closed details and open them to initialize CodeMirror.
          // @see https://github.com/codemirror/codemirror5/issues/61
          let closedDetails = [];
          let parentElement = element.parentNode;
          while (parentElement) {
            if (parentElement.tagName === 'DETAILS'
              && !parentElement.getAttribute('open')) {
              parentElement.setAttribute('open', 'open');
              closedDetails.push(parentElement);
            }
            parentElement = parentElement.parentNode
          }

          // Initialize CodeMirror.
          const editor = CodeMirror.fromTextArea(element, options);

          // Close opened details.
          if (closedDetails) {
            closedDetails.forEach((element) => element.removeAttribute('open'));
          }
        });
    }
  };
})(Drupal, once);
