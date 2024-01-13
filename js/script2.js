// Add this script to handle opening the custom select and populating it with dates
document.addEventListener("click", function (e) {
  const select = document.querySelector(".custom-select");
  if (!select.contains(e.target)) {
    select.classList.remove("open");
  }
});

document
  .querySelector(".custom-select__trigger")
  .addEventListener("click", function () {
    this.parentElement.classList.toggle("open");
  });

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function populateDates() {
  const optionsContainer = document.querySelector(".custom-select__trigger");
  const selectDateOption = document.createElement("option");
  selectDateOption.textContent = "Vælg en dato";
  selectDateOption.disabled = true;
  selectDateOption.selected = true; // The option is selected by default
  optionsContainer.appendChild(selectDateOption);

  for (let i = 0; i < 14; i++) {
    const date = new Date();
    date.setDate(date.getDate() + i);
    const option = document.createElement("option");
    option.className = "custom-option";
    let dateString = date.toLocaleDateString("da-DK", {
      weekday: "long",
      month: "long",
      day: "numeric",
    });
    option.textContent = capitalizeFirstLetter(dateString);
    option.dataset.value = date.toISOString().split("T")[0];

    optionsContainer.appendChild(option);

    // Check if the current day is Saturday (6)
    if (date.getDay() === 6) {
      // Create and append the separator
      const separator = document.createElement("option");
      separator.disabled = true; // Disable the separator so it can't be selected
      separator.textContent = "----------";
      optionsContainer.appendChild(separator);
    }
  }
}

populateDates();

document.addEventListener("DOMContentLoaded", function () {
  var form = document.getElementById("booking_form");
  form.addEventListener("submit", function (event) {
    event.preventDefault();

    console.log("Form submitted with data: ", form);

    var formData = new FormData(form);

    console.log("Form submitted with data: ", formData);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "/wp-admin/admin-ajax.php", true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    formData.append("action", "booking_form_action"); // Action hook name

    xhr.onload = function () {
      if (this.status >= 200 && this.status < 400) {
        // Handle the response here
        var resp = this.response;
        console.log(resp);
      } else {
        // Handle errors here
      }
    };

    xhr.send(formData);
  });
});

function toggleFold(unitId) {
  var formdiv = document.getElementById("foldableDiv-" + unitId);
  var continue_button = document.getElementById("continue-button-" + unitId);
  if (formdiv.style.maxHeight === "0px") {
    formdiv.style.maxHeight = "400px"; // or the full height of the content
    formdiv.style.paddingTop = "1rem"; // or the full height of the content
    continue_button.style.backgroundColor = "#eaeaea";
    // remove the hover effect on .depotrum-row
    var style = document.createElement("style");
    style.innerHTML = `
    .depotrum-list .depotrum-row.partner.yellowhover:hover {
        background-color: #ffffff !important;
      }
    `;
    document.head.appendChild(style);
  } else {
    formdiv.style.maxHeight = "0px";
    continue_button.style.backgroundColor = "#ff336a";
    // remove the no-hover style
    var style = document.getElementById("no-hover");
    if (style) {
      document.head.removeChild(style);
    }
  }
}
