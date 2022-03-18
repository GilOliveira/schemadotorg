/**
 * @file
 * Schema.org UI behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Open Schema.org type and property report links in a modal dialog.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiReportDialog = {
    attach: function (context) {
      $('a[href*="/admin/reports/schemadotorg/"]', context)
        .once('schemadotorg-ui-report-dialog').each(function () {
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
   * Schema.org UI properties toggle behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertiesToggle = {
    attach: function (context) {
      // Toggle selected/mapped properties.
      $('table.schemadotorg-ui-properties', context)
        .once('schemadotorg-ui-properties-toggle')
        .each(function () {
          var $table = $(this);

          var showMappedLabel = Drupal.t('Show mapped properties');
          var showAllLabel = Drupal.t('Show all properties');

          // Create toggle button.
          var button = '<button type="button" class="schemadotorg-ui-properties-toggle link action-link">' + showMappedLabel + '</button>';
          var $toggle = $(button)
            .on('click', function (e) {
              var toggle = $table.data('toggle') || false;

              // Toggle all table rows.
              $table.find('tbody tr').toggle(toggle);

              // Toggle the button's label.
              $(this).html(toggle ? showMappedLabel : showAllLabel);

              // If we are showing mapped, we should show the mapped properties.
              if (!toggle) {
                $table.find('tbody tr.color-warning, tr.color-success').show();
              }

              $table.data('toggle', !toggle);
            })
            .wrap('<div class="schemadotorg-ui-properties-toggle-wrapper"></div>')
            .parent();

          // Prepend toggle button.
          $table.before($toggle);
        });
    }
  };

  /**
   * Schema.org UI properties status behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertyStatus = {
    attach: function (context) {
      $('table.schemadotorg-ui-properties select[name$="[field][name]"]', context)
        .once('schemadotorg-ui-property-status')
        .change(function () {
          var $select = $(this);
          var value = $select.val();
          var defaultValue = '';
          $select.find('option').each(function (index, option) {
            if (option.defaultSelected) {
              defaultValue = option.value;
              return;
            }
          });

          var $tr = $select.parents('tr');
          $tr.removeClass('color-success').removeClass('color-warning');
          if (value) {
            $tr.addClass( (value !== defaultValue) ? 'color-warning' : 'color-success')
          }
        })
    }
  };

  /**
   * Schema.org UI property add field summary behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertyAddFieldSummary = {
    attach: function (context) {

      $('table.schemadotorg-ui-properties .schemadotorg-ui--add-field', context)
        .once('schemadotorg-ui-property-summary')
        .each(function () {
          var $details = $(this);
          $details.find('select')
            .on('change', function () {
              setPropertyAddFieldSummary($details);
            });
          $details.find('input[type="checkbox"]')
            .on('click', function () {
              setPropertyAddFieldSummary($details);
            });
          setPropertyAddFieldSummary($details);
        });

    }
  };

  function setPropertyAddFieldSummary($details) {
    var text = $details.find('select option:selected').text();
    if ($details.find('input[type="checkbox"]').prop("checked")) {
      text += ' (' + Drupal.t('unlimited') + ')';
    }
    $details.drupalSetSummary(text).trigger('summaryUpdated');
  }

} (jQuery, Drupal));
