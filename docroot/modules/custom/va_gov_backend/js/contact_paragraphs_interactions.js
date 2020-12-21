/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal) {
  Drupal.behaviors.vaGovContactParagraphsInteractions = {
    attach: function attach(context) {
      var extensionFieldsSelects = context.querySelectorAll(".field--name-field-phone-number-type select");
      extensionFieldsSelects.forEach(function (ext) {
        ext.addEventListener("change", function () {
          var divWrap = ext.parentElement.parentElement.parentElement;
          var extInput = divWrap.querySelector(".field--name-field-phone-extension");

          if (ext.value === "tel") {
            extInput.style.display = "block";
          } else {
            extInput.style.display = "none";
          }
        });
      });
    }
  };
})(Drupal);