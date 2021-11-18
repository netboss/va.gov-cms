/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.vetCenterVastDataNodeOutputManipulation = {
    attach: function attach(context) {
      if (context.querySelectorAll(".admin-help-email-tpl").length) {
        var emailLinks = context.querySelectorAll(".admin-help-email-tpl");

        var facilityID = context.querySelector(".field--name-field-facility-locator-api-id .field__item") ? context.querySelector(".field--name-field-facility-locator-api-id .field__item").textContent : context.querySelector(".form-item-field-facility-locator-api-id-0-value input").value;

        var facilityName = context.querySelector(".breadcrumb li:last-child") !== null ? context.querySelector(".breadcrumb li:last-child").textContent.trim() : "";

        emailLinks.forEach(function (emailLink) {
          var eHref = emailLink.href;
          emailLink.setAttribute("href", eHref.replace("[js_entry_facility_name]", facilityName).replace("[js_entry_facility_name]", facilityName).replace("[js_entry_facility_id]", facilityID));
        });

        var adminRoles = ["content_admin", "administrator"];
        var targetTypes = ["vet_center"];

        if (drupalSettings.gtm_data.contentType && targetTypes.some(function (item) {
          return drupalSettings.gtm_data.contentType.includes(item);
        }) && !adminRoles.some(function (item) {
          return drupalSettings.gtm_data.userRoles.includes(item);
        })) {
          var targetFieldGroup = context.querySelector(".node__content > .not-editable.tooltip-layout");

          var facilityDataFieldGroup = context.createElement("div");

          var legend = context.createElement("h3");
          legend.style.fontFamily = "Lucida Grande, Lucida Sans Unicode, DejaVu Sans, Lucida Sans, sans-serif";
          legend.style.fontSize = "1rem";
          legend.innerHTML = "FACILITY DATA";

          var label = context.createElement("div");
          label.classList.add("field__label");
          label.innerHTML = "Name of facility";

          var fieldItem = context.createElement("div");
          var description = context.querySelector("#locations-and-contact-information .tooltip-layout .description");
          fieldItem.classList.add("field__item");
          fieldItem.innerHTML = facilityName;

          targetFieldGroup.insertBefore(fieldItem, targetFieldGroup.firstChild);

          targetFieldGroup.insertBefore(label, targetFieldGroup.firstChild);

          targetFieldGroup.insertBefore(description, targetFieldGroup.firstChild);

          targetFieldGroup.insertBefore(legend, targetFieldGroup.firstChild);

          targetFieldGroup.appendChild(facilityDataFieldGroup);

          var topOfPage = context.querySelector("#top-of-page-information .tooltip-layout");
          var topOfPageHelp = context.getElementById("top-get-help-email");
          topOfPage.insertBefore(topOfPageHelp, topOfPage.firstChild);
        }
      }
    }
  };
})(jQuery, window.Drupal);