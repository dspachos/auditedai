/**
 * @file
 * JavaScript behaviors for the audit node view.
 */

(function ($, Drupal, once) {
  "use strict";

  /**
   * Custom behavior for audit node view.
   */
  Drupal.behaviors.auditNodeView = {
    attach: function (context, settings) {
      // Handle custom tooltip functionality
      $(once('audit-tooltip', '[data-audit-tooltip]', context)).each(function () {
        var $icon = $(this);
        var tooltipText = $icon.data('audit-tooltip');
        
        // Create tooltip element
        var $tooltip = $('<div class="audit-tooltip">' + tooltipText + '</div>');
        $('body').append($tooltip); // Append to body to avoid clipping
        
        // Toggle tooltip on click
        $icon.on('click', function (e) {
          e.stopPropagation();
          
          // Hide any other tooltips
          $('.audit-tooltip.show').removeClass('show');
          
          // Position tooltip
          positionTooltip($icon, $tooltip);
          
          // Show this tooltip
          $tooltip.toggleClass('show');
        });
      });
      
      // Hide tooltip when clicking elsewhere
      $(document).on('click', function () {
        $('.audit-tooltip.show').removeClass('show');
      });
      
      // Position tooltip within viewport
      function positionTooltip($icon, $tooltip) {
        var iconOffset = $icon.offset();
        var iconWidth = $icon.outerWidth();
        var tooltipWidth = $tooltip.outerWidth();
        var tooltipHeight = $tooltip.outerHeight();
        
        // Reset classes
        $tooltip.removeClass('above');
        
        // Initial position (centered below icon)
        var top = iconOffset.top + $icon.outerHeight() + 5;
        var left = iconOffset.left + (iconWidth / 2) - (tooltipWidth / 2);
        
        // Adjust for viewport boundaries
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();
        var scrollTop = $(window).scrollTop();
        
        // Adjust left position if tooltip goes off screen
        if (left < 5) {
          left = 5;
        } else if (left + tooltipWidth > windowWidth - 5) {
          left = windowWidth - tooltipWidth - 5;
        }
        
        // Adjust top position if tooltip goes off screen
        if (top + tooltipHeight > scrollTop + windowHeight - 5) {
          // Position above icon instead
          top = iconOffset.top - tooltipHeight - 5;
          $tooltip.addClass('above');
        }
        
        $tooltip.css({
          top: top,
          left: left
        });
      }
    },
  };
})(jQuery, Drupal, once);
