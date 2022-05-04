/**
 * @file
 * Schema.org UI behaviors.
 */

(function ($, Drupal, debounce, once) {

  'use strict';

  /**
   * Schema.org UI properties filter by text.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertiesFilterByText = {
    attach: function attach(context) {
      $('input.schemadotorg-ui-properties-filter-text', context)
        .once('schemadotorg-ui-properties-filter-text')
        .each( function () {
          // Input
          var $input = $(this);

          // Reset.
          var $reset = $('<input class="schemadotorg-ui-properties-filter-reset" type="button" title="Clear the search query." value="✕" style="display: none" />');
          $reset.on('click', resetFilter);
          $reset.insertAfter($input);

          // Filter rows.
          var $filterRows;
          var $table = $('table.schemadotorg-ui-properties');
          if ($table.length) {
            $filterRows = $table.find('div.schemadotorg-ui-property');
            $input.on('keyup', debounce(filterBlockList, 200));
          }

          // Make sure the filter input is alway empty when the page loadis
          setTimeout(function () {$input.val('');}, 100);

          function resetFilter() {
            $input.val('').keyup();
            $input.trigger('focus');
          }

          function filterBlockList(e) {
            var query = $(e.target).val().toLowerCase();

            function toggleBlockEntry(index, label) {
              var $label = $(label);
              var $row = $label.parent().parent();
              var textMatch = $label.text().toLowerCase().includes(query);
              $row.toggleClass('schemadotorg-ui-properties-filter-match', textMatch);
            }

            // Use CSS to hide/show matches that the hide/show mapped properties
            // state is preserved.
            if (query.length >= 2) {
              $table.addClass('schemadotorg-ui-properties-filter-matches');
              $filterRows.each(toggleBlockEntry);
              Drupal.announce(Drupal.formatPlural($table.find('tr:visible').length - 1, '1 property is available in the modified list.', '@count properties are available in the modified list.'));
            } else {
              $table.removeClass('schemadotorg-ui-properties-filter-matches');
              $filterRows.each(function () {
                $(this).parent().parent().removeClass('schemadotorg-ui-properties-filter-match');
              });
            }

            // Hide/show reset.
            $reset[query.length ? 'show' : 'hide']();
          }
        });
    }
  };

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

          var hideUnmappedLabel = Drupal.t('Hide unmapped');
          var showUnmappedLabel = Drupal.t('Show unmapped');

          var toggleKey = 'schemadotorg-ui-properties-toggle';

          // If toggle key does not exist, set its default state.
          if (localStorage.getItem(toggleKey) === null) {
            localStorage.setItem(toggleKey, '0');
          }

          // Create toggle button.
          var button = '<button type="button" class="schemadotorg-ui-properties-toggle link action-link action-link--extrasmall"></button>';
          var $toggleButton = $(button).on('click', function toggleButtonClick() {
            var toggle = localStorage.getItem(toggleKey);
            localStorage.setItem(toggleKey, (toggle === '1') ? '0' : '1');
            toggleProperties();
          });

          // Prepend toggle element with wrapper the table.
          var $toggle = $toggleButton
            .wrap('<div class="schemadotorg-ui-properties-toggle-wrapper"></div>')
            .parent();
          $table.before($toggle);

          // Initialize properties toggle.
          toggleProperties();

          // Show the table after it has been fully initialized.
          $table.show();

          function toggleProperties() {
            var showAll = (localStorage.getItem('schemadotorg-ui-properties-toggle') === '0');
            if (showAll) {
              $toggleButton
                .html(hideUnmappedLabel)
                .removeClass('action-link--icon-show')
                .addClass('action-link--icon-hide');
              $table.find('tbody tr').show();
            }
            else {
              $toggleButton
                .html(showUnmappedLabel)
                .removeClass('action-link--icon-hide')
                .addClass('action-link--icon-show');
              $table.find('tbody tr').hide();
              $table.find('tbody tr.color-warning, tbody tr.color-success').show();
            }
          }
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
      $('details[data-schemadotorg-ui-summary]', context)
        .once('schemadotorg-ui-property-summary')
        .each(function () {
          var $details = $(this);
          var text = $details.data('schemadotorg-ui-summary')
         $details.drupalSetSummary(text).trigger('summaryUpdated');
        });

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
      text += ' - ' + Drupal.t('unlimited');
    }
    $details.drupalSetSummary(text).trigger('summaryUpdated');
  }

} (jQuery, Drupal, Drupal.debounce, once));
