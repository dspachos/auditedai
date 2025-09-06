/**
 * @file
 * VP Visual Editor behaviors.
 */
(function (Drupal, drupalSettings, $, once) {
  "use strict";

  Drupal.behaviors.vpVisualEditorVpVisualEditor = {
    attach: function (context, settings) {
      once("vpEditor", "body", context).forEach(() => {
        var id = document.getElementById("drawflow");
        const editor = new Drawflow(id);
        editor.reroute = false;
        editor.reroute_fix_curvature = false;
        editor.zoom_value = 0.01;
        editor.start();
        editor.import(drupalSettings.visualData);
        var baselineVisualData = drupalSettings.visualData;

        editor.on("nodeCreated", function (id) {});

        function allowDrop(event) {
          console.log(event.target);
        }

        addEventListener("dblclick", (event) => {
          const element = $("#edit-" + event.target.id);
          element.trigger("click");
        });

        setInterval(function () {
          const el = $("#visual-message");
          const exportData = editor.export();
          if (
            JSON.stringify(exportData) !== JSON.stringify(baselineVisualData)
          ) {
            el.html(Drupal.t('Saving.. <i class="bi bi-floppy"></i>'));
            $(".js-button-add").addClass("disabled");
            $.ajax({
              url: Drupal.url(
                "virtual-patient/visual-edit/save?vp=" + drupalSettings.vp
              ),
              cache: false,
              data: JSON.stringify(exportData),
              contentType: "application/json",
              type: "POST",
              dataType: "json",
              success: function (response) {
                $(".js-button-add").removeClass("disabled");
                baselineVisualData = response;
                el.html(
                  Drupal.t(
                    'All changes saved <i class="bi bi-check-lg success"></i>'
                  )
                );
              },
              error: function () {
                // @todo
                el.html(
                  Drupal.t(
                    'There was an error, could not save changes <i class="bi bi-exclamation-triangle-fill warning"></i>'
                  )
                );
              },
            });
          }
        }, 3000);

        $(".js-vp-save-button").on("click", function () {
          const exportData = editor.export();
          $.ajax({
            url: Drupal.url("virtual-patient/visual-edit/save"),
            cache: false,
            data: JSON.stringify(exportData),
            contentType: "application/json",
            type: "POST",
            dataType: "json",
            success: function (response) {
              console.log("🚀 ~ response:", response);
            },
            error: function () {
              // @todo
            },
          });
        });
      });
    }, // end once
  };
})(Drupal, drupalSettings, jQuery, once);
