CRM.$(function ($) {
  var $statusValueField = $('[name="acceptance_status_value"]');
  var $acceptanceStatusField = $('[name="acceptance_status_field"]');
  var isFirstLoad = true; // Track if this is the first load

  // Function to fetch and populate options
  function populateAcceptanceStatusOptions(customFieldId) {
    // Clear previous options and reset the select field
    $statusValueField
      .empty()
      .append(
        $("<option>", {
          value: "",
          text: "Loading options...", // Temporary loading message
          disabled: true,
        })
      )
      .trigger("change");

    if (customFieldId) {
      CRM.api4("OptionValue", "get", {
        join: [
          [
            "CustomField AS custom_field",
            "LEFT",
            ["custom_field.option_group_id", "=", "option_group_id"],
          ],
        ],
        where: [["custom_field.id", "=", customFieldId]],
      }).then(function (result) {
        $statusValueField.empty();
        if (result.length > 0) {
          $statusValueField.append(
            $("<option>", {
              value: "",
              text: "- Select Option Value -",
              disabled: true,
              selected: true,
            })
          );
          $.each(result, function (index, option) {
            var $optionElement = $("<option>", {
              value: option.value,
              text: option.label,
            });

            $statusValueField.append($optionElement);
          });

          // Apply the saved value only on the first load
          if (isFirstLoad && savedAcceptanceStatusValue) {
            $statusValueField.val(savedAcceptanceStatusValue).trigger("change");
          }

          // After first load, disable further auto-population of saved value
          isFirstLoad = false;
        } else {
          $statusValueField.append(
            $("<option>", {
              value: "",
              text: "No options found",
              disabled: true,
            })
          );
        }
        $statusValueField.select2({
          placeholder: "- Select Option Value -",
          allowClear: true,
        });
      });
    }
  }

  // Trigger the population on page load (if the field has an initial value)
  var initialCustomFieldId = $acceptanceStatusField.val();
  if (initialCustomFieldId) {
    populateAcceptanceStatusOptions(initialCustomFieldId);
  }

  // Event listener for when the acceptance status field changes
  $acceptanceStatusField.change(function () {
    var customFieldId = $(this).val();

    // Clear the currently selected option
    $statusValueField.val(null).trigger("change");

    // Reset select2 to properly clear it
    $statusValueField.select2("destroy"); // Destroy the current select2 instance

    // Populate options based on the new field selection
    populateAcceptanceStatusOptions(customFieldId);
  });

  // Initialize select2 on page load with placeholder for the acceptance_status_value field
  $statusValueField.select2({
    placeholder: "- Select Option Value -",
    allowClear: true,
  });
});
