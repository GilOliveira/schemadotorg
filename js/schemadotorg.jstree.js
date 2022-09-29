/**
 * @file
 * Schema.org jsTree behaviors.
 */

(function ($, Drupal, once) {

  'use strict';

  var jsTreeConfig = {
    "core" : {
      "themes" : {
        "icons": false,
      },
    },
  };

  /**
   * Schema.org Report jsTree behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgJsTree = {
    attach: function (context) {
      $(once('schemadotorg-jstree', '.schemadotorg-jstree', context))
        .each(function () {
          var $tree = $(this);

          // Remove <div> from nested list markup.
          $tree.html(
            $tree.html().replace(/<\/?div[^>]*>/g, '')
          );

          var $jstree = $tree.parent();
          $jstree.jstree(jsTreeConfig);

          // Enable links.
          // @see https://stackoverflow.com/questions/8378561/js-tree-links-not-active
          $jstree.on("activate_node.jstree", function (e, data) {
            var href = data.node.a_attr.href;
            if (Drupal.schemaDotOrgOpenDialog) {
              Drupal.schemaDotOrgOpenDialog(href);
            }
            else {
              window.location.href = href;
            }
            return false;
          });

          // Create toggle button.
          var collapseLabel = Drupal.t('Collapse all');
          var expandLabel = Drupal.t('Expand all');

          var button = '<button type="button" class="schemadotorg-jstree-toggle link action-link">' + expandLabel + '</button>';
          var $toggle = $(button)
            .on('click', function (e) {
              var toggle = $jstree.data('toggle') || false;
              if (!toggle) {
                $jstree.jstree('open_all');
              }
              else {
                $jstree.jstree('close_all');
              }
              $(this).html(toggle ? expandLabel : collapseLabel);
              $jstree.data('toggle', !toggle);
            })
            .wrap('<div class="schemadotorg-jstree-toggle-wrapper"></div>')
            .parent();

          // Prepend toggle button.
          $jstree.before($toggle);

      });
    }
  }

} (jQuery, Drupal, once));
