/**
 * @file
 * JavaScript behaviors for the audit node view.
 */

(function ($, Drupal) {
  "use strict";

  /**
   * Custom behavior for audit node view.
   */
  Drupal.behaviors.auditNodeView = {
    attach: function (context, settings) {
      // Add any custom JavaScript functionality here
      once("intiOnce", "html").forEach(function (element) {
        console.log("🚀");
      });
    },
  };
})(jQuery, Drupal, once);
