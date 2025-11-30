/**
 * @file
 * JavaScript for the AJAX Yes/No checkbox functionality.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';

  // Function to handle the yes/no change via AJAX
  window.handleYesNoChange = function(checkbox) {
    var questionId = $(checkbox).data('question-id');
    var auditId = $(checkbox).data('audit-id');
    var isChecked = checkbox.checked;

    // Add temporary visual feedback
    $(checkbox).addClass('ajax-processing');

    // Perform AJAX request to save the value
    $.ajax({
      url: Drupal.url('audit/ajax-toggle-yes-no/' + auditId + '/' + questionId),
      type: 'POST',
      data: {
        'value': isChecked ? 1 : 0,
        'question_id': questionId,
        'audit_id': auditId
      },
      dataType: 'json',
      success: function(response) {
        // Update the label text based on the new checkbox state
        var toggleLabel = $(checkbox).closest('.toggle-container').find('.toggle-label');
        toggleLabel.text(isChecked ? Drupal.t('Yes') : Drupal.t('No'));

        // Add success visual feedback
        $(checkbox).addClass('ajax-success');

        // Remove success class after delay
        setTimeout(function() {
          $(checkbox).removeClass('ajax-success ajax-processing');
        }, 1000);
      },
      error: function(xhr, status, error) {
        // Revert the checkbox state on error
        checkbox.checked = !isChecked;

        // Update the label text to match the reverted state
        var toggleLabel = $(checkbox).closest('.toggle-container').find('.toggle-label');
        toggleLabel.text(!isChecked ? Drupal.t('Yes') : Drupal.t('No'));

        // Remove processing class
        $(checkbox).removeClass('ajax-processing');

        console.error('Error saving yes/no value: ', error);
        alert('Error saving value. Please try again.');
      }
    });
  };

  Drupal.behaviors.auditAjaxYesNoCheckbox = {
    attach: function (context, settings) {
      // Ensure the function is available in Drupal context
      if (typeof window.handleYesNoChange === 'undefined') {
        window.handleYesNoChange = function(checkbox) {
          var questionId = $(checkbox).data('question-id');
          var auditId = $(checkbox).data('audit-id');
          var isChecked = checkbox.checked;

          // Add temporary visual feedback
          $(checkbox).addClass('ajax-processing');

          // Perform AJAX request to save the value
          $.ajax({
            url: Drupal.url('audit/ajax-toggle-yes-no/' + auditId + '/' + questionId),
            type: 'POST',
            data: {
              'value': isChecked ? 1 : 0,
              'question_id': questionId,
              'audit_id': auditId
            },
            dataType: 'json',
            success: function(response) {
              // Update the label text based on the new checkbox state
              var toggleLabel = $(checkbox).closest('.toggle-container').find('.toggle-label');
              toggleLabel.text(isChecked ? Drupal.t('Yes') : Drupal.t('No'));

              // Add success visual feedback
              $(checkbox).addClass('ajax-success');

              // Remove success class after delay
              setTimeout(function() {
                $(checkbox).removeClass('ajax-success ajax-processing');
              }, 1000);
            },
            error: function(xhr, status, error) {
              // Revert the checkbox state on error
              checkbox.checked = !isChecked;

              // Update the label text to match the reverted state
              var toggleLabel = $(checkbox).closest('.toggle-container').find('.toggle-label');
              toggleLabel.text(!isChecked ? Drupal.t('Yes') : Drupal.t('No'));

              // Remove processing class
              $(checkbox).removeClass('ajax-processing');

              console.error('Error saving yes/no value: ', error);
              alert('Error saving value. Please try again.');
            }
          });
        };
      }
    }
  };

})(jQuery, Drupal, drupalSettings);